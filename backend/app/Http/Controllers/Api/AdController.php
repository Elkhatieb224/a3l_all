<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdListResource;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use App\Jobs\ProcessAdImagesJob;
use App\Support\AdImagesConfig;
use App\Support\AdLocationPayload;
use App\Support\AdVideoUpload;
use App\Support\AdPublishFailureLogger;
use App\Support\CustomFieldsFilterSupport;
use App\Support\CustomFieldsResolver;
use App\Support\SearchCategoryAdHitRows;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdController extends Controller
{
    private const MIN_SEARCH_LENGTH = 3;

    private const LIST_COLUMNS = [
        'id',
        'uid',
        'user_id',
        'category_id',
        'subcategory_id',
        'title',
        'price',
        'currency',
        'images',
        'video',
        'custom_fields',
        'location_country',
        'location_state',
        'location_state_code',
        'location_city',
        'location_city_code',
        'location_district',
        'location_district_code',
        'location_address',
        'location_input_method',
        'show_location',
        'latitude',
        'longitude',
        'views_count',
        'status',
        'is_featured',
        'is_urgent',
        'published_at',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search', 'featured', 'filter', 'statistics']);
    }

    public function index(Request $request)
    {
        $perPage = $this->resolvePerPage($request);
        $page = max((int) $request->input('page', 1), 1);
        $user = $this->resolveOptionalUser($request);

        $guestIndexCacheable = !$user && $page === 1 && ! $this->requestHasAdsIndexListFilters($request);

        if ($guestIndexCacheable) {
            $cacheKey = $this->buildIndexCacheKey($request, $perPage);
            $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($request, $perPage) {
                $query = $this->baseListQuery(null);
                $this->applyCommonListFilters($query, $request, false, true); // searchDescription=true مثل الموقع
                $this->applyAdsListSort($query, $request);
                $ads = $query->paginate($perPage);

                return [
                    'success' => true,
                    'data' => collect($ads->items())
                        ->map(fn ($ad) => (new AdListResource($ad))->toArray($request))
                        ->values()
                        ->all(),
                    'meta' => $this->paginationMeta($ads),
                ];
            });

            return response()->json($payload)
                ->header('Cache-Control', 'public, max-age=30, stale-while-revalidate=60');
        }

        $query = $this->baseListQuery($user);
        $this->applyCommonListFilters($query, $request, false, true); // searchDescription=true مثل الموقع
        $this->applyAdsListSort($query, $request);
        $ads = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdListResource::collection($ads),
            'meta' => $this->paginationMeta($ads),
        ]);
    }

    public function show(Request $request, $uid)
    {
        $user = $this->resolveOptionalUser($request);
        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        $query = Ad::where('status', 'active')
            ->where('uid', $uid)
            ->with([
                'category' => fn ($q) => $q->select('id', 'name_ar', 'name_en', 'name_tr', 'slug', 'enable_negotiation', 'custom_fields'),
                'subcategory' => function ($q) {
                    $q->select('id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr', 'slug', 'custom_fields')
                        ->with('parent.parent.parent.parent');
                },
                'user' => fn ($q) => $q->select('id', 'name', 'business_name', 'slug', 'avatar', 'is_verified', 'phone', 'country_code', 'location_country'),
            ])
            ->withCount(['conversations', 'favorites']);

        if ($user) {
            $query->withExists([
                'favorites as is_favorite' => fn ($f) => $f->where('user_id', $user->id),
            ]);
        }

        $ad = $query->firstOrFail();

        if ($user && in_array((int) $ad->user_id, $this->blockedUserIdsFor($user), true)) {
            abort(404);
        }

        $ad->incrementViews();

        $relatedQuery = Ad::where('category_id', $ad->category_id)
            ->where('id', '!=', $ad->id)
            ->where('status', 'active')
            ->select(self::LIST_COLUMNS)
            ->with([
                'category:id,name_ar,name_en,name_tr',
                'subcategory:id,name_ar,name_en,name_tr',
                'user:id,name,business_name,slug,avatar,is_verified',
            ]);
        if ($user) {
            $relatedQuery->withExists([
                'favorites as is_favorite' => fn ($f) => $f->where('user_id', $user->id),
            ]);
            $blockedIds = $this->blockedUserIdsFor($user);
            if (!empty($blockedIds)) {
                $relatedQuery->whereNotIn('user_id', $blockedIds);
            }
        }
        $relatedAds = $relatedQuery->latest('published_at')->take(6)->get();

        $promoteActions = null;
        if ($user && (int) $ad->user_id === (int) $user->id && $ad->status === 'active') {
            $promoteActions = [
                'can_add_featured' => $user->canCreateFeaturedAd() && !$ad->is_featured,
                'can_remove_featured' => (bool) $ad->is_featured,
                'can_add_urgent' => $user->canCreateUrgentAd() && !$ad->is_urgent,
                'can_remove_urgent' => (bool) $ad->is_urgent,
                'remaining_featured' => $user->getRemainingFeaturedAds(),
                'remaining_urgent' => $user->getRemainingUrgentAds(),
            ];
        }

        $payload = [
            'success' => true,
            'data' => new AdResource($ad),
            'related_ads' => AdListResource::collection($relatedAds),
        ];
        if ($promoteActions !== null) {
            $payload['promote_actions'] = $promoteActions;
        }

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user can create a free ad
        if (!$user->canCreateFreeAd()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'free_ads_limit',
                (string) __('frontend.ads.free_ads_limit_reached'),
                ['http_status' => 403, 'redirect_to' => 'packages']
            );

            return response()->json([
                'success' => false,
                'message' => __('frontend.ads.free_ads_limit_reached'),
                'redirect_to' => 'packages',
            ], 403);
        }

        $baseRules = [
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SYP,TRY,USD,EUR',
            'custom_fields' => 'nullable|array',
        ];

        [$passes, $validator] = AdLocationPayload::validateWithMergedRules($request, $baseRules);
        if (! $passes) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'validation_failed',
                'Base or location validation failed on API ad store',
                [
                    'http_status' => 422,
                    'errors' => $validator->errors()->toArray(),
                    'category_id' => $request->input('category_id'),
                    'subcategory_id' => $request->input('subcategory_id'),
                    'title_preview' => mb_substr((string) $request->input('title', ''), 0, 120),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = Category::findOrFail($request->category_id);
        $subcategory = Subcategory::findOrFail($request->subcategory_id);
        if ((int) $subcategory->category_id !== (int) $category->id) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'subcategory_mismatch',
                'Subcategory does not belong to the selected category',
                [
                    'http_status' => 422,
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Subcategory does not belong to the selected category.',
                'errors' => ['subcategory_id' => ['invalid']],
            ], 422);
        }

        $imgCfg = AdImagesConfig::resolve($category, $subcategory);
        $maxImages = (int) ($imgCfg['max_images'] ?? AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES);
        $imageRules = $imgCfg['mode'] === AdImagesConfig::MODE_ADMIN_GALLERY
            ? [
                'gallery_image' => ['required', 'string', Rule::in($imgCfg['gallery_paths'])],
            ]
            : [
                'images' => 'required|array|min:1|max:'.$maxImages,
                'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            ];

        $validatorImages = Validator::make($request->all(), $imageRules, [
            'images.max' => __('frontend.ads.images_max_count', ['max' => $maxImages]),
        ]);
        if ($validatorImages->fails()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'images_validation_failed',
                'Images or gallery_image validation failed',
                [
                    'http_status' => 422,
                    'errors' => $validatorImages->errors()->toArray(),
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validatorImages->errors(),
            ], 422);
        }

        $validatorVideo = Validator::make($request->all(), [
            'video' => 'nullable|file|mimes:mp4,mov,webm|max:'.AdVideoUpload::maxSizeKbForValidator(),
        ]);
        if ($validatorVideo->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validatorVideo->errors(),
            ], 422);
        }
        if ($request->hasFile('video')) {
            $vErrs = AdVideoUpload::validate($request->file('video'));
            if ($vErrs !== []) {
                return response()->json([
                    'success' => false,
                    'message' => $vErrs[0],
                    'errors' => ['video' => $vErrs],
                ], 422);
            }
        }

        // Validate custom fields (resolved from subcategory path, deepest wins)
        $customFields = [];
        $fieldSchema = \App\Support\CustomFieldsResolver::resolveActiveFields($category, $subcategory);
        if ($request->has('custom_fields')) {
            foreach ($fieldSchema as $field) {
                if (!($field['is_active'] ?? true)) {
                    continue;
                }

                $fieldId = $field['id'] ?? 'field_' . array_search($field, $fieldSchema);
                $isRequired = $field['required'] ?? false;
                $fieldType = $field['type'] ?? 'text';

                $reqVal = $request->custom_fields[$fieldId] ?? null;
                $isTbd = is_array($reqVal) && !empty($reqVal['tbd']);
                $allowTbd = !empty($field['allow_tbd']);
                if ($isRequired && !isset($request->custom_fields[$fieldId])) {
                    AdPublishFailureLogger::log(
                        AdPublishFailureLogger::SOURCE_API,
                        $request,
                        'custom_field_required',
                        "Required custom field missing: {$fieldId}",
                        [
                            'http_status' => 422,
                            'field_id' => $fieldId,
                            'field_type' => $fieldType,
                            'category_id' => $category->id,
                            'subcategory_id' => $subcategory->id,
                        ]
                    );

                    return response()->json([
                        'success' => false,
                        'message' => "Field {$fieldId} is required",
                        'errors' => ["custom_fields.{$fieldId}" => ['This field is required']]
                    ], 422);
                }
                if ($isRequired && $fieldType === 'number' && !empty($field['show_currency']) && isset($request->custom_fields[$fieldId]) && is_array($reqVal)) {
                    $hasValue = isset($reqVal['value']) && (string)$reqVal['value'] !== '' && is_numeric($reqVal['value']);
                    if (!$isTbd && !$hasValue) {
                        AdPublishFailureLogger::log(
                            AdPublishFailureLogger::SOURCE_API,
                            $request,
                            'custom_field_price_incomplete',
                            "Required number+currency field incomplete: {$fieldId}",
                            [
                                'http_status' => 422,
                                'field_id' => $fieldId,
                                'category_id' => $category->id,
                                'subcategory_id' => $subcategory->id,
                            ]
                        );

                        return response()->json([
                            'success' => false,
                            'message' => "Field {$fieldId} is required (provide value or tbd)",
                            'errors' => ["custom_fields.{$fieldId}" => ['This field is required']]
                        ], 422);
                    }
                }
                if ($isRequired && $fieldType === 'location') {
                    $lat = $request->custom_fields[$fieldId]['latitude'] ?? null;
                    $lng = $request->custom_fields[$fieldId]['longitude'] ?? null;
                    if ($lat === null || $lng === null || $lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                        AdPublishFailureLogger::log(
                            AdPublishFailureLogger::SOURCE_API,
                            $request,
                            'custom_field_location_incomplete',
                            "Required location field incomplete: {$fieldId}",
                            [
                                'http_status' => 422,
                                'field_id' => $fieldId,
                                'category_id' => $category->id,
                                'subcategory_id' => $subcategory->id,
                            ]
                        );

                        return response()->json([
                            'success' => false,
                            'message' => __('frontend.ads.location_required'),
                            'errors' => ["custom_fields.{$fieldId}" => [__('frontend.ads.location_required')]]
                        ], 422);
                    }
                }

                if (isset($request->custom_fields[$fieldId])) {
                    if ($fieldType === 'location') {
                        $customFields[$fieldId] = [
                            'latitude' => $request->custom_fields[$fieldId]['latitude'] ?? null,
                            'longitude' => $request->custom_fields[$fieldId]['longitude'] ?? null,
                            'address' => $request->custom_fields[$fieldId]['address'] ?? null,
                        ];
                    } elseif ($fieldType === 'number' && !empty($field['show_currency']) && is_array($request->custom_fields[$fieldId])) {
                        if (!empty($request->custom_fields[$fieldId]['tbd'])) {
                            $customFields[$fieldId] = ['tbd' => true];
                        } else {
                            $val = $request->custom_fields[$fieldId]['value'] ?? $request->custom_fields[$fieldId];
                            $cur = $request->custom_fields[$fieldId]['currency'] ?? \App\Models\Setting::get('default_currency', 'SYP');
                            $customFields[$fieldId] = ['value' => $val !== null && $val !== '' ? (is_numeric($val) ? $val + 0 : $val) : null, 'currency' => $cur ?: \App\Models\Setting::get('default_currency', 'SYP')];
                        }
                    } else {
                        $customFields[$fieldId] = $request->custom_fields[$fieldId];
                    }
                }
            }
        }

        $customFields = \App\Support\SellerTypeField::applyLockedOwner(
            $customFields,
            $fieldSchema,
            $user,
            'ar'
        );

        // صور الإعلان: رفع من المستخدم أو مسار من معرض لوحة التحكم
        $images = [];
        if ($imgCfg['mode'] === AdImagesConfig::MODE_ADMIN_GALLERY) {
            $images = [(string) $request->input('gallery_image')];
        } else {
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $images[] = store_ad_image_raw($image);
                }
            }
        }

        if ($imgCfg['mode'] !== AdImagesConfig::MODE_ADMIN_GALLERY && $images === []) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'no_valid_images',
                'No images stored after upload on API ad store',
                [
                    'http_status' => 422,
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => __('frontend.ads.images_none_valid'),
                'errors' => ['images' => [__('frontend.ads.images_none_valid')]],
            ], 422);
        }

        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = store_ad_video_raw($request->file('video'));
        }

        $isFeatured = $request->boolean('is_featured');
        $isUrgent = $request->boolean('is_urgent');

        if ($isFeatured && !$user->canCreateFeaturedAd()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'featured_limit',
                (string) __('frontend.ads.featured_limit_reached'),
                ['http_status' => 422, 'category_id' => $category->id, 'subcategory_id' => $subcategory->id]
            );

            return response()->json(['success' => false, 'message' => __('frontend.ads.featured_limit_reached')], 422);
        }
        if ($isUrgent && !$user->canCreateUrgentAd()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'urgent_limit',
                (string) __('frontend.ads.urgent_limit_reached'),
                ['http_status' => 422, 'category_id' => $category->id, 'subcategory_id' => $subcategory->id]
            );

            return response()->json(['success' => false, 'message' => __('frontend.ads.urgent_limit_reached')], 422);
        }

        $validatedBase = $validator->validated();
        $locRow = AdLocationPayload::normalizedForDatabase($validatedBase, $request);
        $latitude = $locRow['latitude'];
        $longitude = $locRow['longitude'];
        $locationAddress = $locRow['location_address'];
        if (($latitude === null || $longitude === null) && ! empty($customFields)) {
            foreach ($fieldSchema as $field) {
                if (($field['type'] ?? '') === 'location') {
                    $fieldId = $field['id'] ?? 'field_'.array_search($field, $fieldSchema);
                    $loc = $customFields[$fieldId] ?? null;
                    if (is_array($loc)) {
                        $lat = $loc['latitude'] ?? $loc['lat'] ?? null;
                        $lng = $loc['longitude'] ?? $loc['lng'] ?? null;
                        if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
                            $latitude = (float) $lat;
                            $longitude = (float) $lng;
                            if (($locationAddress === null || $locationAddress === '') && ! empty($loc['address'])) {
                                $locationAddress = (string) $loc['address'];
                            }
                            break;
                        }
                    }
                }
            }
        }

        [$adPrice, $adCurrency] = $this->resolveAdPriceAndCurrency(
            $request,
            $customFields,
            $fieldSchema
        );

        // Create the ad
        try {
            $ad = Ad::create([
                'user_id' => $user->id,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $adPrice,
                'currency' => $adCurrency,
                'images' => $images,
                'video' => $videoPath,
                'custom_fields' => $customFields,
                'location_country' => $locRow['location_country'],
                'location_state' => $locRow['location_state'],
                'location_state_code' => $locRow['location_state_code'],
                'location_city' => $locRow['location_city'],
                'location_city_code' => $locRow['location_city_code'],
                'location_district' => $locRow['location_district'],
                'location_district_code' => $locRow['location_district_code'],
                'location_address' => $locationAddress,
                'location_input_method' => $locRow['location_input_method'],
                'show_location' => $locRow['show_location'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => 'pending',
                'published_at' => now(),
                'is_featured' => $isFeatured,
                'is_urgent' => $isUrgent,
            ]);
        } catch (\Throwable $e) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_API,
                $request,
                'database_exception',
                $e->getMessage(),
                [
                    'http_status' => 500,
                    'category_id' => $request->category_id,
                    'subcategory_id' => $request->subcategory_id,
                    'exception_class' => get_class($e),
                ]
            );
            throw $e;
        }

        // Cumulative subscriptions: consume quotas from active packages.
        $user->consumeAdQuota();
        if ($isFeatured) {
            $user->consumeFeaturedQuota();
        }
        if ($isUrgent) {
            $user->consumeUrgentQuota();
        }

        if ($imgCfg['mode'] !== AdImagesConfig::MODE_ADMIN_GALLERY && $images !== []) {
            ProcessAdImagesJob::dispatch($ad->id, $images)->afterResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ad created successfully',
            'data' => new AdResource($ad->load(['category', 'subcategory', 'user']))
        ], 201);
    }

    public function update(Request $request, $uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        $input = $request->all();
        if (empty($input) && in_array($request->getMethod(), ['PUT', 'PATCH'], true) && $request->getContent()) {
            parse_str($request->getContent(), $input);
            $request->merge($input);
        }

        $category = $ad->category;
        $subcategory = $ad->subcategory;
        $maxImages = AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES;
        if ($category && $subcategory) {
            $maxImages = AdImagesConfig::resolveMaxImages($category, $subcategory);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SYP,TRY,USD,EUR',
            'images' => 'nullable|array|max:'.$maxImages,
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,webm|max:'.AdVideoUpload::maxSizeKbForValidator(),
            'custom_fields' => 'nullable|array',
        ], [
            'images.max' => __('frontend.ads.images_max_count', ['max' => $maxImages]),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('video')) {
            $vErrs = AdVideoUpload::validate($request->file('video'));
            if ($vErrs !== []) {
                return response()->json([
                    'success' => false,
                    'message' => $vErrs[0],
                    'errors' => ['video' => $vErrs],
                ], 422);
            }
        }

        $validated = $validator->validated();

        // بناء التغييرات المعلقة فقط (مثل الموقع /profile/ads/{uid}/edit)
        $pendingChanges = [];
        $currentCustomFields = $ad->custom_fields ?? [];
        $customFieldsStructure = [];
        foreach (\App\Support\CustomFieldsResolver::resolveActiveFields($category, $subcategory) as $field) {
            $fieldId = $field['id'] ?? 'field_'.array_search($field, $field);
            $customFieldsStructure[$fieldId] = $field;
        }

        if (($validated['title'] ?? '') !== (string) $ad->title) {
            $pendingChanges['title'] = $validated['title'];
        }
        if (($validated['description'] ?? '') !== (string) $ad->description) {
            $pendingChanges['description'] = $validated['description'];
        }
        if ($request->has('price') || $request->has('currency')) {
            $newPrice = isset($validated['price']) ? (is_numeric($validated['price']) ? (float) $validated['price'] : null) : null;
            $cur = $validated['currency'] ?? $ad->currency ?? 'SYP';
            if ($newPrice != $ad->price) {
                $pendingChanges['price'] = $newPrice;
                $pendingChanges['currency'] = $cur;
            } elseif ($request->has('currency') && $cur !== ($ad->currency ?? 'SYP')) {
                $pendingChanges['price'] = $ad->price;
                $pendingChanges['currency'] = $cur;
            }
        }

        $customFieldValues = [];
        $reqCustom = $request->input('custom_fields') ?? [];
        foreach ($customFieldsStructure as $fieldId => $field) {
            $raw = $reqCustom[$fieldId] ?? $request->input($fieldId);
            if ($request->has('custom_fields') && isset($reqCustom[$fieldId])) {
                $raw = $reqCustom[$fieldId];
                if (($field['type'] ?? 'text') === 'location' && is_array($raw)) {
                    $fieldValue = [
                        'latitude' => $raw['latitude'] ?? null,
                        'longitude' => $raw['longitude'] ?? null,
                        'address' => $raw['address'] ?? null,
                    ];
                } elseif (($field['type'] ?? '') === 'number' && !empty($field['show_currency']) && is_array($raw)) {
                    if (!empty($raw['tbd'])) {
                        $fieldValue = ['tbd' => true];
                    } else {
                        $val = $raw['value'] ?? null;
                        $fieldValue = [
                            'value' => $val !== null && $val !== '' ? (is_numeric($val) ? (float) $val : $val) : null,
                            'currency' => !empty($raw['currency']) ? $raw['currency'] : \App\Models\Setting::get('default_currency', 'SYP'),
                        ];
                    }
                } else {
                    $fieldValue = $raw;
                }
            } else {
                $fieldValue = $raw;
            }
            $currentValue = $currentCustomFields[$fieldId] ?? null;
            $normalizedNew = $fieldValue;
            if (is_array($normalizedNew) && array_key_exists('value', $normalizedNew)) {
                $empty = ($normalizedNew['value'] ?? '') === '' || ($normalizedNew['value'] ?? null) === null;
            } else {
                $empty = $normalizedNew === null || $normalizedNew === '';
            }
            $currentFilled = !$empty;
            if (is_array($currentValue) && array_key_exists('value', $currentValue)) {
                $currentFilled = (($currentValue['value'] ?? '') !== '' && ($currentValue['value'] ?? null) !== null);
            } elseif ($currentValue !== null && $currentValue !== '') {
                $currentFilled = true;
            }
            if ($empty && $currentFilled) continue;
            if ($empty && !$currentFilled) continue;
            if ($normalizedNew == $currentValue) continue;
            $customFieldValues[$fieldId] = $normalizedNew;
        }

        if (! $user->is_verified) {
            $schema = array_values($customFieldsStructure);
            $merged = array_merge($currentCustomFields, $customFieldValues);
            $merged = \App\Support\SellerTypeField::applyLockedOwner($merged, $schema, $user, 'ar');
            $locked = $merged[\App\Support\SellerTypeField::FIELD_ID] ?? null;
            if ($locked !== null) {
                $currentSeller = $currentCustomFields[\App\Support\SellerTypeField::FIELD_ID] ?? null;
                if ($currentSeller != $locked) {
                    $customFieldValues[\App\Support\SellerTypeField::FIELD_ID] = $locked;
                } else {
                    unset($customFieldValues[\App\Support\SellerTypeField::FIELD_ID]);
                }
            }
        }

        if (!empty($customFieldValues)) {
            $pendingChanges['custom_fields'] = $customFieldValues;
        }

        if (!isset($pendingChanges['price']) && !empty($customFieldValues)) {
            foreach ($customFieldValues as $val) {
                if (is_array($val) && isset($val['value'], $val['currency']) && ($val['value'] !== '' && $val['value'] !== null)) {
                    $pendingChanges['price'] = is_numeric($val['value']) ? (float) $val['value'] : $val['value'];
                    $pendingChanges['currency'] = $val['currency'];
                    break;
                }
            }
        }

        $newImages = null;
        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $newImages[] = store_ad_image_raw($image);
            }
            if ($newImages != ($ad->images ?? [])) {
                $pendingChanges['images'] = $newImages;
            }
        }

        if ($request->hasFile('video')) {
            $oldPendingVideo = is_array($ad->pending_changes) ? ($ad->pending_changes['video'] ?? null) : null;
            $newVideo = store_ad_video_raw($request->file('video'));
            $currentVideo = (string) ($ad->video ?? '');
            if ($newVideo !== $currentVideo) {
                $pendingChanges['video'] = $newVideo;
            }
            if (is_string($oldPendingVideo) && $oldPendingVideo !== '' && $oldPendingVideo !== ($pendingChanges['video'] ?? null)) {
                Storage::disk('public')->delete($oldPendingVideo);
            }
        }

        if (empty($pendingChanges)) {
            if ($ad->pending_changes) {
                $ad->update(['pending_changes' => null]);
            }
            return response()->json([
                'success' => true,
                'message' => __('frontend.profile.ads.no_changes_detected'),
                'data' => new AdResource($ad->fresh()->load(['category', 'subcategory', 'user']))
            ]);
        }

        $ad->update(['pending_changes' => $pendingChanges]);

        if ($newImages !== null && $newImages !== []) {
            ProcessAdImagesJob::dispatch($ad->id, $newImages)->afterResponse();
        }

        return response()->json([
            'success' => true,
            'message' => __('frontend.profile.ads.changes_pending_review'),
            'data' => new AdResource($ad->fresh()->load(['category', 'subcategory', 'user']))
        ]);
    }

    public function suspend($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        if ($ad->status === 'suspended') {
            return response()->json([
                'success' => true,
                'message' => __('frontend.profile.ads.ad_already_suspended'),
                'data' => new AdResource($ad->fresh()->load(['category', 'subcategory', 'user']))
            ]);
        }

        $ad->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => __('frontend.profile.ads.ad_suspended'),
            'data' => new AdResource($ad->fresh()->load(['category', 'subcategory', 'user']))
        ]);
    }

    public function unsuspend($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        if ($ad->status !== 'suspended') {
            return response()->json([
                'success' => true,
                'message' => __('frontend.profile.ads.ad_not_suspended'),
                'data' => new AdResource($ad->fresh()->load(['category', 'subcategory', 'user']))
            ]);
        }

        if (!$user->canUnsuspendAd()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.profile.ads.unsuspend_limit_reached'),
                'redirect_to' => 'packages',
            ], 403);
        }

        $ad->update(['status' => 'active', 'published_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => __('frontend.profile.ads.ad_unsuspended'),
            'data' => new AdResource($ad->fresh()->load(['category', 'subcategory', 'user']))
        ]);
    }

    public function destroy($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        // Delete images
        foreach ($ad->images ?? [] as $image) {
            Storage::disk('public')->delete($image);
        }
        if (is_string($ad->video) && $ad->video !== '') {
            Storage::disk('public')->delete($ad->video);
        }

        $ad->delete();

        return response()->json([
            'success' => true,
            'message' => __('frontend.profile.ads.ad_deleted')
        ]);
    }

    /**
     * عرض تفاصيل إعلان المستخدم (أي حالة: active, pending, rejected, expired)
     */
    public function myAdShow($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()
            ->where('uid', $uid)
            ->with(['category', 'subcategory' => function ($q) {
                $q->with('parent.parent.parent.parent');
            }])
            ->withCount(['conversations', 'favorites'])
            ->firstOrFail();

        // Related ads (active only) from same category
        $relatedAds = Ad::where('category_id', $ad->category_id)
            ->where('id', '!=', $ad->id)
            ->where('status', 'active')
            ->with(['category', 'subcategory'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $promoteActions = null;
        if ($ad->status === 'active') {
            $promoteActions = [
                'can_add_featured' => $user->canCreateFeaturedAd() && !$ad->is_featured,
                'can_remove_featured' => (bool) $ad->is_featured,
                'can_add_urgent' => $user->canCreateUrgentAd() && !$ad->is_urgent,
                'can_remove_urgent' => (bool) $ad->is_urgent,
                'remaining_featured' => $user->getRemainingFeaturedAds(),
                'remaining_urgent' => $user->getRemainingUrgentAds(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => new AdResource($ad),
            'related_ads' => AdListResource::collection($relatedAds),
            'promote_actions' => $promoteActions,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * تمييز الإعلان أو إلغاء التميز — نفس منطق الموقع (profile.ads.set-featured)
     */
    public function setFeatured($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        if ($ad->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => __('frontend.profile.ads.only_active_can_promote'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if ($ad->is_featured) {
            $ad->update(['is_featured' => false]);
            $user->releaseFeaturedQuota();
            return response()->json([
                'success' => true,
                'message' => __('frontend.profile.ads.featured_removed'),
                'is_featured' => false,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$user->canCreateFeaturedAd()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.ads.featured_limit_reached'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $ad->update(['is_featured' => true]);
        $user->consumeFeaturedQuota();
        return response()->json([
            'success' => true,
            'message' => __('frontend.profile.ads.featured_added'),
            'is_featured' => true,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    public function setUrgent($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        if ($ad->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => __('frontend.profile.ads.only_active_can_promote'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if ($ad->is_urgent) {
            $ad->update(['is_urgent' => false]);
            $user->releaseUrgentQuota();
            return response()->json([
                'success' => true,
                'message' => __('frontend.profile.ads.urgent_removed'),
                'is_urgent' => false,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$user->canCreateUrgentAd()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.ads.urgent_limit_reached'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $ad->update(['is_urgent' => true]);
        $user->consumeUrgentQuota();
        return response()->json([
            'success' => true,
            'message' => __('frontend.profile.ads.urgent_added'),
            'is_urgent' => true,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function myAds(Request $request)
    {
        $user = Auth::user();

        $query = $user->ads()
            ->select(self::LIST_COLUMNS)
            ->with([
                'category:id,name_ar,name_en,name_tr',
                'subcategory:id,name_ar,name_en,name_tr',
            ])
            ->withCount(['conversations', 'favorites']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $ads = $query->latest()->paginate($this->resolvePerPage($request));

        return response()->json([
            'success' => true,
            'data' => AdListResource::collection($ads),
            'meta' => $this->paginationMeta($ads),
        ]);
    }

    public function search(Request $request)
    {
        $user = $this->resolveOptionalUser($request);
        $query = $this->baseListQuery($user);
        $this->applyCommonListFilters($query, $request, true, true); // بحث في الوصف مثل الموقع
        $this->applyAdsListSort($query, $request);
        $ads = $query->paginate($this->resolvePerPage($request));

        return response()->json([
            'success' => true,
            'data' => AdListResource::collection($ads),
            'meta' => $this->paginationMeta($ads),
        ]);
    }

    /**
     * البحث عن الفئات الرئيسية التي تحتوي على إعلانات مطابقة للكلمة (عناوين، أسماء فئات/فوئات فرعية بجميع اللغات).
     * لا يُنفّذ البحث بأقل من 3 أحرف.
     */
    public function searchCategories(Request $request)
    {
        $q = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));

        if ($q === '' || mb_strlen($q) < self::MIN_SEARCH_LENGTH) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'min_length' => self::MIN_SEARCH_LENGTH,
            ]);
        }

        // منطق مطابق للويب: لا نستبعد المستخدمين المحظورين عند عرض الفئات حتى تظهر نفس النتائج
        $baseAdQuery = Ad::query()
            ->where('status', 'active')
            ->select('id', 'category_id');
        $this->applySearchCondition($baseAdQuery, $q, true);

        $categoryIds = (clone $baseAdQuery)->select('category_id')->distinct()->pluck('category_id')->filter()->values()->toArray();

        if (empty($categoryIds)) {
            return response()
                ->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'min_length' => self::MIN_SEARCH_LENGTH,
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->whereIn('id', $categoryIds)
            ->orderBy('order')
            ->get(['id', 'name_ar', 'name_en', 'name_tr', 'icon', 'order']);

        $counts = (clone $baseAdQuery)->selectRaw('category_id, count(*) as cnt')->groupBy('category_id')->pluck('cnt', 'category_id');

        $locale = app()->getLocale();
        $data = SearchCategoryAdHitRows::rowsWithBreadcrumbs(
            $baseAdQuery,
            $categories,
            $counts->all(),
            $locale
        );

        $total = (int) array_sum(array_column($data, 'matching_ads_count'));

        return response()
            ->json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'min_length' => self::MIN_SEARCH_LENGTH,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Get featured/premium ads
     */
    public function featured(Request $request)
    {
        $user = $this->resolveOptionalUser($request);
        $query = $this->baseListQuery($user)
            ->where('is_featured', true);
        $ads = $query->latest('published_at')->paginate($this->resolvePerPage($request));

        return response()->json([
            'success' => true,
            'data' => AdListResource::collection($ads),
            'meta' => $this->paginationMeta($ads),
        ]);
    }

    /**
     * Advanced filter for ads
     */
    public function filter(Request $request)
    {
        $user = $this->resolveOptionalUser($request);
        $query = $this->baseListQuery($user);
        $this->applyCommonListFilters($query, $request, true, true);
        $this->applyAdsListSort($query, $request);

        $ads = $query->paginate($this->resolvePerPage($request));

        return response()->json([
            'success' => true,
            'data' => AdListResource::collection($ads),
            'meta' => $this->paginationMeta($ads),
        ]);
    }

    /**
     * Get ad statistics
     */
    public function statistics($uid)
    {
        $ad = Ad::where('status', 'active')
            ->where('uid', $uid)
            ->withCount(['conversations', 'favorites'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'views' => $ad->views_count ?? 0,
                'messages_count' => (int) ($ad->conversations_count ?? 0),
                'favorites_count' => (int) ($ad->favorites_count ?? 0),
            ],
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 20), 1), 50);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function resolveOptionalUser(Request $request): ?User
    {
        if (!$request->bearerToken()) {
            return null;
        }

        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user();

        return $user;
    }

    private function baseListQuery(?User $user): Builder
    {
        $query = Ad::query()
            ->select(self::LIST_COLUMNS)
            ->where('status', 'active')
            ->with([
                'category:id,name_ar,name_en,name_tr',
                'subcategory:id,name_ar,name_en,name_tr',
                'user:id,name,business_name,slug,avatar,is_verified',
            ]);

        if ($user) {
            $query->withExists([
                'favorites as is_favorite' => fn ($favoritesQuery) => $favoritesQuery->where('user_id', $user->id),
            ]);
            $blockedIds = $this->blockedUserIdsFor($user);
            if (!empty($blockedIds)) {
                $query->whereNotIn('user_id', $blockedIds);
            }
        }

        return $query;
    }

    /**
     * @return list<int>
     */
    private function blockedUserIdsFor(User $user): array
    {
        return Cache::remember(
            'api:user:'.$user->id.':blocked_user_ids',
            now()->addMinutes(2),
            fn () => $user->blockedUsers()->pluck('blocked_user_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    /**
     * تطبيق شرط البحث: عناوين الإعلانات + أسماء الفئات والفوئات الفرعية بجميع اللغات.
     */
    private function applySearchCondition(Builder $query, string $searchValue, bool $searchDescription = false): void
    {
        $variants = $this->searchLikeVariants($searchValue);
        $query->where(function (Builder $q) use ($variants, $searchDescription) {
            foreach ($variants as $like) {
                $q->orWhere('title', 'like', $like);
                if ($searchDescription) {
                    $q->orWhere('description', 'like', $like);
                }
                $q->orWhereHas('category', function (Builder $c) use ($like) {
                    $c->where('name_ar', 'like', $like)
                        ->orWhere('name_en', 'like', $like)
                        ->orWhere('name_tr', 'like', $like);
                });
                $this->applySubcategoryNameMatch($q, $like);
            }
        });
    }

    private function applySubcategoryNameMatch(Builder $query, string $like): void
    {
        $query->orWhereHas('subcategory', function (Builder $s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
        $query->orWhereHas('subcategory.parent', function (Builder $s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
        $query->orWhereHas('subcategory.parent.parent', function (Builder $s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
        $query->orWhereHas('subcategory.parent.parent.parent', function (Builder $s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
    }

    /**
     * تجهيز بدائل بحث تتعامل مع: الهمزات العربية + المسافات (بي إم / بي ام / بيم).
     *
     * @return array<int,string>
     */
    private function searchLikeVariants(string $searchValue): array
    {
        $raw = trim($searchValue);
        if ($raw === '') {
            return ['%%'];
        }

        $collapseSpaces = preg_replace('/\s+/u', ' ', $raw) ?? $raw;
        $noSpaces = str_replace(' ', '', $collapseSpaces);
        $normalized = strtr($collapseSpaces, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ى' => 'ي',
            'ة' => 'ه',
        ]);
        $normalizedNoSpaces = str_replace(' ', '', $normalized);

        $variants = array_values(array_unique(array_filter([
            $raw,
            $collapseSpaces,
            $noSpaces,
            $normalized,
            $normalizedNoSpaces,
        ], fn ($v) => is_string($v) && trim($v) !== '')));

        return array_map(fn ($v) => '%' . $v . '%', $variants);
    }

    public static function getMinSearchLength(): int
    {
        return self::MIN_SEARCH_LENGTH;
    }

    private function applyCommonListFilters(
        Builder $query,
        Request $request,
        bool $useQSearch = false,
        bool $searchDescription = false
    ): void {
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('subcategory_id')) {
            $subcategoryId = (int) $request->input('subcategory_id');
            // يجب تحميل category_id وإلا getDescendantIds() يستخدم 0 ولا يُرجع الأبناء فيُستبعد كل إعلان الشجرة
            $sub = Subcategory::query()->select(['id', 'category_id'])->find($subcategoryId);
            $ids = $sub ? $sub->getDescendantIds() : [$subcategoryId];
            $query->whereIn('subcategory_id', $ids);
        }

        if ($request->filled('featured') && $request->input('featured') === '1') {
            $query->where('is_featured', true);
        }

        if ($request->filled('urgent') && $request->input('urgent') === '1') {
            $query->where('is_urgent', true);
        }

        $this->applyPriceFilter($query, $request);

        if ($request->filled('location_country')) {
            $query->where('location_country', $request->input('location_country'));
        }

        $districtCodes = $this->normalizeLocationFilterList($request->input('location_districts'));
        $cityCodes = $this->normalizeLocationFilterList($request->input('location_cities'));
        $stateCodes = $this->normalizeLocationFilterList($request->input('location_states'));

        if ($districtCodes !== []) {
            $query->where(function (Builder $q) use ($districtCodes) {
                $q->whereIn('location_district_code', $districtCodes)
                    ->orWhereIn('location_district', $districtCodes);
            });
        }
        if ($cityCodes !== []) {
            $query->where(function (Builder $q) use ($cityCodes) {
                $q->whereIn('location_city_code', $cityCodes)
                    ->orWhereIn('location_city', $cityCodes);
            });
        }
        if ($stateCodes !== []) {
            $query->where(function (Builder $q) use ($stateCodes) {
                $q->whereIn('location_state_code', $stateCodes)
                    ->orWhereIn('location_state', $stateCodes);
            });
        }

        if ($districtCodes === [] && $cityCodes === [] && $stateCodes === [] && $request->filled('location_city')) {
            $query->where('location_city', 'like', '%' . $request->input('location_city') . '%');
        }

        $searchValue = trim((string) ($useQSearch ? $request->input('q') : ($request->input('search') ?? $request->input('q'))));
        if ($searchValue !== '' && mb_strlen($searchValue) >= self::MIN_SEARCH_LENGTH) {
            $this->applySearchCondition($query, $searchValue, $searchDescription);
        }

        $this->applyCustomFieldFilters($query, $request);
    }

    /**
     * تطبيق فلتر السعر: يشمل الإعلانات التي لها سعر في العمود price أو في custom_fields.
     */
    private function applyPriceFilter(Builder $query, Request $request): void
    {
        $fields = $this->resolveSchemaFieldsForFilterRequest($request);
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($fields);
        [$minPrice, $maxPrice] = CustomFieldsFilterSupport::normalizedMinMaxPrice($request, $priceFieldId);

        if ($minPrice === null && $maxPrice === null) {
            return;
        }

        $priceField = $priceFieldId
            ? CustomFieldsFilterSupport::findFieldById($fields, $priceFieldId)
            : null;
        $usesCurrencyObject = $priceField && CustomFieldsFilterSupport::customFieldPriceUsesCurrencyObject($priceField);

        $query->where(function (Builder $q) use ($minPrice, $maxPrice, $priceFieldId, $usesCurrencyObject) {
            $q->where(function (Builder $q2) use ($minPrice, $maxPrice) {
                $q2->whereNotNull('price');
                if ($minPrice !== null) {
                    $q2->where('price', '>=', $minPrice);
                }
                if ($maxPrice !== null) {
                    $q2->where('price', '<=', $maxPrice);
                }
            });

            if ($priceFieldId) {
                $q->orWhere(function (Builder $q2) use ($minPrice, $maxPrice, $priceFieldId, $usesCurrencyObject) {
                    if ($usesCurrencyObject) {
                        $cfPath = "custom_fields->{$priceFieldId}->value";
                        $q2->whereNotNull($cfPath);
                        if ($minPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}.value')) + 0) >= ?", [$minPrice]);
                        }
                        if ($maxPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}.value')) + 0) <= ?", [$maxPrice]);
                        }
                    } else {
                        $q2->whereNotNull("custom_fields->{$priceFieldId}");
                        if ($minPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}')) + 0) >= ?", [$minPrice]);
                        }
                        if ($maxPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}')) + 0) <= ?", [$maxPrice]);
                        }
                    }
                });
            }
        });
    }

    /**
     * @return array{0: mixed, 1: string}
     */
    private function resolveAdPriceAndCurrency(Request $request, array $customFields, array $fieldSchema): array
    {
        $defaultCurrency = $request->input('currency') ?? \App\Models\Setting::get('default_currency', 'SYP');
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($fieldSchema);

        if ($priceFieldId && $customFields !== []) {
            $extracted = CustomFieldsFilterSupport::extractPriceAndCurrencyFromCustomFields(
                $customFields,
                $priceFieldId,
                $defaultCurrency
            );
            if ($extracted['price'] !== null) {
                return [$extracted['price'], $extracted['currency'] ?? $defaultCurrency];
            }
        }

        if ($customFields !== []) {
            $extracted = CustomFieldsFilterSupport::extractPriceAndCurrencyFromCustomFields(
                $customFields,
                null,
                $defaultCurrency
            );
            if ($extracted['price'] !== null) {
                return [$extracted['price'], $extracted['currency'] ?? $defaultCurrency];
            }
        }

        $price = $request->input('price');
        if ($price !== null && $price !== '') {
            return [$price, $defaultCurrency];
        }

        return [null, $defaultCurrency];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveSchemaFieldsForFilterRequest(Request $request): array
    {
        $categoryId = (int) $request->input('category_id', 0);
        $subcategoryId = (int) $request->input('subcategory_id', 0);

        if ($subcategoryId) {
            $sub = Subcategory::query()
                ->select(['id', 'category_id', 'parent_subcategory_id', 'custom_fields'])
                ->with('category:id,custom_fields')
                ->find($subcategoryId);
            if ($sub) {
                return CustomFieldsResolver::resolveActiveFields($sub->category, $sub);
            }
        }

        if ($categoryId) {
            $cat = Category::query()
                ->select(['id', 'custom_fields'])
                ->find($categoryId);
            if ($cat) {
                return CustomFieldsResolver::resolveActiveFields($cat, null);
            }
        }

        return [];
    }

    private function resolvePriceFieldIdFromRequest(Request $request): ?string
    {
        return CustomFieldsFilterSupport::resolvePrimaryPriceFieldId(
            $this->resolveSchemaFieldsForFilterRequest($request)
        );
    }

    /**
     * تطبيق الفلاتر القادمة من الحقول المخصصة (custom_fields) حسب الفئة / الفئة الفرعية.
     *
     * توقُّع صيغة البارامترات من الـ API:
     * - الحقول الرقمية (type=number):
     *      cf_{id}_min = الحد الأدنى
     *      cf_{id}_max = الحد الأقصى
     * - القوائم (type=select):
     *      cf_{id} = قيمة الخيار (id أو القيمة المخزّنة)
     * - مربعات الاختيار (type=checkbox):
     *      cf_{id} = 1 / true / yes  (تعني مفعّل فقط)
     */
    private function applyCustomFieldFilters(Builder $query, Request $request): void
    {
        $fields = $this->resolveSchemaFieldsForFilterRequest($request);
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($fields);
        $fields = CustomFieldsFilterSupport::resolveFilterableFields($fields);

        if ($fields === []) {
            return;
        }

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            if (!($field['is_active'] ?? true)) {
                continue;
            }

            $id = $field['id'] ?? null;
            $type = $field['type'] ?? 'text';
            if (!$id || !is_string($id)) {
                continue;
            }

            if ($priceFieldId !== null && $id === $priceFieldId) {
                continue;
            }

            $minParam = $request->input("cf_{$id}_min");
            $maxParam = $request->input("cf_{$id}_max");
            $eqParam = $request->input("cf_{$id}");

            if ($type === 'number') {
                $jsonPath = "custom_fields->$id";
                if (!empty($field['show_currency'])) {
                    $jsonPath .= '->value';
                }

                if ($minParam !== null && $minParam !== '') {
                    $query->where($jsonPath, '>=', (float) $minParam);
                }
                if ($maxParam !== null && $maxParam !== '') {
                    $query->where($jsonPath, '<=', (float) $maxParam);
                }
            } elseif ($type === 'select') {
                if ($eqParam !== null && $eqParam !== '') {
                    $options = $field['options'] ?? [];
                    $valuesToMatch = [$eqParam];
                    foreach ($options as $opt) {
                        if (!is_array($opt)) {
                            continue;
                        }
                        $optVal = $opt['id'] ?? ($opt['ar'] ?? ($opt['en'] ?? ($opt['tr'] ?? null)));
                        if ((string) $optVal === (string) $eqParam) {
                            $valuesToMatch = array_values(array_unique(array_filter([
                                $eqParam,
                                $opt['ar'] ?? null,
                                $opt['en'] ?? null,
                                $opt['tr'] ?? null,
                            ])));
                            break;
                        }
                    }
                    $query->where(function (Builder $q) use ($id, $valuesToMatch) {
                        $q->whereIn("custom_fields->$id", $valuesToMatch);
                    });
                }
            } elseif ($type === 'checkbox') {
                if ($eqParam !== null && $eqParam !== '') {
                    $value = strtolower((string) $eqParam);
                    $boolVal = in_array($value, ['1', 'true', 'yes', 'on'], true);
                    $query->where("custom_fields->$id", $boolVal);
                }
            } elseif ($type === 'date') {
                CustomFieldsFilterSupport::applyDateAfterFilter(
                    $query,
                    $id,
                    $request->input("cf_{$id}_after")
                );
            } elseif ($type === 'car_body_map') {
                CustomFieldsFilterSupport::applyCarBodyMapPartFilters($query, $id, $request);
            }
        }
    }

    /**
     * فرز قائمة الإعلانات — نفس قيم sort_by التي يرسلها تطبيق Flutter (GET /ads).
     */
    private function applyAdsListSort(Builder $query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'date_desc');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'date_asc':
                $query->orderBy('published_at', 'asc');
                break;
            case 'date_desc':
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }
        $query->orderBy('id', 'desc');
    }

    /**
     * أي فلترة للقوائم (قسم/فرعي/بحث/سعر/…) تُستثنى من كاش الصفحة الأولى للزائر
     * حتى لا تُعرض لقطة JSON قديمة لا تطابق أعداد الشجرة أو إعلاناتاً حديثة.
     */
    private function requestHasAdsIndexListFilters(Request $request): bool
    {
        if ($request->filled('category_id')) {
            return true;
        }
        if ($request->filled('subcategory_id')) {
            return true;
        }
        if ($request->filled('featured') || $request->filled('urgent')) {
            return true;
        }
        if ($request->filled('search') || $request->filled('q')) {
            return true;
        }
        if ($request->filled('min_price') || $request->filled('max_price')) {
            return true;
        }
        if ($request->filled('location_country') || $request->filled('location_city')) {
            return true;
        }
        if ($this->normalizeLocationFilterList($request->input('location_states')) !== []
            || $this->normalizeLocationFilterList($request->input('location_cities')) !== []
            || $this->normalizeLocationFilterList($request->input('location_districts')) !== []) {
            return true;
        }

        return collect($request->all())->keys()->contains(fn ($k) => str_starts_with((string) $k, 'cf_'));
    }

    private function buildIndexCacheKey(Request $request, int $perPage): string
    {
        $customFilters = collect($request->all())->filter(fn ($v, $k) => str_starts_with((string) $k, 'cf_'))->all();

        $cachePayload = [
            'version' => (int) Cache::get('api:ads:index:version', 1),
            'locale' => app()->getLocale(),
            'per_page' => $perPage,
            'category_id' => $request->input('category_id'),
            'subcategory_id' => $request->input('subcategory_id'),
            'featured' => $request->input('featured'),
            'urgent' => $request->input('urgent'),
            'search' => $request->input('search'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'sort_by' => $request->get('sort_by', 'date_desc'),
            'custom_filters' => $customFilters,
            'location_states' => $this->normalizeLocationFilterList($request->input('location_states')),
            'location_cities' => $this->normalizeLocationFilterList($request->input('location_cities')),
            'location_districts' => $this->normalizeLocationFilterList($request->input('location_districts')),
        ];

        return 'api:ads:index:v1:' . sha1(json_encode($cachePayload));
    }

    /**
     * @return list<string>
     */
    private function normalizeLocationFilterList(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $parts = array_map('trim', explode(',', $raw));

            return array_values(array_filter($parts, fn ($s) => $s !== ''));
        }
        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $item) {
                if ($item === null || $item === '') {
                    continue;
                }
                $s = is_scalar($item) ? (string) $item : '';
                $s = trim($s);
                if ($s !== '') {
                    $out[] = $s;
                }
            }

            return array_values(array_unique($out));
        }

        return [];
    }
}
