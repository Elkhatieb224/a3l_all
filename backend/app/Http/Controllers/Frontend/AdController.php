<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\SearchHistory;
use App\Jobs\ProcessAdImagesJob;
use App\Support\AdImagesConfig;
use App\Support\AdLocationPayload;
use App\Support\AdVideoUpload;
use App\Support\CustomFieldsFilterSupport;
use App\Support\CustomFieldsResolver;
use App\Support\AdPublishFailureLogger;
use App\Support\SearchCategoryAdHitRows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $query = Ad::where('status', 'active')
            ->with(['category', 'subcategory', 'user']);

        // Filters
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }

        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->has('urgent')) {
            $query->where('is_urgent', true);
        }

        // Time filter for urgent ads
        if ($request->has('time')) {
            $timeFilter = $request->time;
            switch ($timeFilter) {
                case 'month':
                    $query->where('published_at', '>=', now()->subMonth());
                    break;
                case 'week':
                    $query->where('published_at', '>=', now()->subWeek());
                    break;
                case '48h':
                    $query->where('published_at', '>=', now()->subHours(48));
                    break;
            }
        }

        $minSearchLength = \App\Http\Controllers\Api\AdController::getMinSearchLength();
        if ($request->has('search')) {
            $searchTerm = trim($request->search);
            if ($searchTerm !== '' && mb_strlen($searchTerm) >= $minSearchLength) {
                $variants = $this->searchLikeVariants($searchTerm);
                $query->where(function ($q) use ($variants) {
                    foreach ($variants as $like) {
                        $q->orWhere('title', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhereHas('category', function ($c) use ($like) {
                                $c->where('name_ar', 'like', $like)
                                    ->orWhere('name_en', 'like', $like)
                                    ->orWhere('name_tr', 'like', $like);
                            });
                        $this->applySubcategoryNameMatch($q, $like);
                    }
                });
                if (Auth::check()) {
                    SearchHistory::saveSearch($searchTerm, Auth::id());
                }
            }
        }

        $ads = $query->latest('published_at')->paginate(20);
        $categories = Category::active()->ordered()->get();

        $searchCategories = [];
        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '' && mb_strlen($searchTerm) >= $minSearchLength) {
            $variants = $this->searchLikeVariants($searchTerm);
            $baseQuery = Ad::where('status', 'active')
                ->where(function ($q) use ($variants) {
                    foreach ($variants as $like) {
                        $q->orWhere('title', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhereHas('category', function ($c) use ($like) {
                                $c->where('name_ar', 'like', $like)->orWhere('name_en', 'like', $like)->orWhere('name_tr', 'like', $like);
                            });
                        $this->applySubcategoryNameMatch($q, $like);
                    }
                });
            $categoryIds = (clone $baseQuery)->distinct()->pluck('category_id');
            $counts = (clone $baseQuery)->selectRaw('category_id, count(*) as cnt')->groupBy('category_id')->pluck('cnt', 'category_id');
            $cats = Category::where('is_active', true)->whereIn('id', $categoryIds)->orderBy('order')->get();
            foreach ($cats as $cat) {
                $searchCategories[] = (object) [
                    'id' => $cat->id,
                    'name' => $cat->getName(app()->getLocale()),
                    'matching_ads_count' => (int) ($counts[$cat->id] ?? 0),
                ];
            }
        }

        return view('frontend.ads.index', compact('ads', 'categories', 'searchCategories', 'searchTerm'));
    }

    /**
     * إرجاع الفئات التي تحتوي إعلانات مطابقة للبحث بصيغة JSON (نفس منطق الموقع والـ API).
     * للتطبيق عندما يعيد /api/v1/ads/search-categories رد 401.
     */
    public function searchCategoriesJson(Request $request)
    {
        $minSearchLength = \App\Http\Controllers\Api\AdController::getMinSearchLength();
        $q = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));
        if ($q === '' || mb_strlen($q) < $minSearchLength) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'min_length' => $minSearchLength,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $variants = $this->searchLikeVariants($q);
        $baseQuery = Ad::where('status', 'active')
            ->where(function ($query) use ($variants) {
                foreach ($variants as $like) {
                    $query->orWhere('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('category', function ($c) use ($like) {
                            $c->where('name_ar', 'like', $like)->orWhere('name_en', 'like', $like)->orWhere('name_tr', 'like', $like);
                        });
                    $this->applySubcategoryNameMatch($query, $like);
                }
            });

        $categoryIds = (clone $baseQuery)->select('category_id')->distinct()->pluck('category_id')->filter()->values()->toArray();
        if (empty($categoryIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'min_length' => $minSearchLength,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $categories = Category::where('is_active', true)
            ->whereIn('id', $categoryIds)
            ->orderBy('order')
            ->get(['id', 'name_ar', 'name_en', 'name_tr', 'icon', 'order']);

        $counts = (clone $baseQuery)->selectRaw('category_id, count(*) as cnt')->groupBy('category_id')->pluck('cnt', 'category_id');
        $locale = app()->getLocale();
        $data = SearchCategoryAdHitRows::rowsWithBreadcrumbs(
            $baseQuery,
            $categories,
            $counts->all(),
            $locale
        );
        $total = (int) array_sum(array_column($data, 'matching_ads_count'));

        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ];
        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'min_length' => $minSearchLength,
        ], 200, $headers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * بدائل مطابقة للبحث العربي (مسافات + همزات) مثل: بي إم / بي ام / بيم.
     *
     * @return array<int,string>
     */
    private function searchLikeVariants(string $searchValue): array
    {
        $raw = trim($searchValue);
        if ($raw === '') return ['%%'];

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

    private function applySubcategoryNameMatch($query, string $like): void
    {
        $query->orWhereHas('subcategory', function ($s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
        $query->orWhereHas('subcategory.parent', function ($s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
        $query->orWhereHas('subcategory.parent.parent', function ($s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
        $query->orWhereHas('subcategory.parent.parent.parent', function ($s) use ($like) {
            $s->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_tr', 'like', $like);
        });
    }

    public function show($uid)
    {
        $ad = Ad::where('status', 'active')
            ->where('uid', $uid)
            ->with(['category', 'subcategory' => function ($q) {
                $q->with('parent.parent.parent.parent');
            }, 'user'])
            ->firstOrFail();

        // Increment views
        $ad->incrementViews();

        // Get related ads (same category)
        $relatedAds = Ad::where('category_id', $ad->category_id)
            ->where('id', '!=', $ad->id)
            ->where('status', 'active')
            ->with(['category', 'subcategory'])
            ->latest('published_at')
            ->take(6)
            ->get();

        // Check if ad is favorite for authenticated user
        $isFavorite = false;
        $isOwner = false;
        $canAddFeatured = false;
        $canRemoveFeatured = false;
        $canAddUrgent = false;
        $canRemoveUrgent = false;
        $remainingFeatured = 0;
        $remainingUrgent = 0;

        if (Auth::check()) {
            $isFavorite = $ad->isFavoriteBy(Auth::id());
            $isOwner = (int) $ad->user_id === (int) Auth::id();
            if ($isOwner) {
                $user = Auth::user();
                $canAddFeatured = $ad->status === 'active' && $user->canCreateFeaturedAd() && !$ad->is_featured;
                $canRemoveFeatured = $ad->status === 'active' && $ad->is_featured;
                $canAddUrgent = $ad->status === 'active' && $user->canCreateUrgentAd() && !$ad->is_urgent;
                $canRemoveUrgent = $ad->status === 'active' && $ad->is_urgent;
                $remainingFeatured = $user->getRemainingFeaturedAds();
                $remainingUrgent = $user->getRemainingUrgentAds();
            }
        }

        return view('frontend.ads.show', compact(
            'ad', 'relatedAds', 'isFavorite', 'isOwner',
            'canAddFeatured', 'canRemoveFeatured', 'canAddUrgent', 'canRemoveUrgent',
            'remainingFeatured', 'remainingUrgent'
        ));
    }

    public function create()
    {
        // Step 1: Select Category
        $categories = Category::active()->ordered()->get();
        return view('frontend.ads.create.step1-category', compact('categories'));
    }

    public function selectCategory(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $category = Category::findOrFail($request->category_id);

        // Store in session
        session(['ad_data' => [
            'category_id' => $category->id,
            'category_name' => $category->getName(app()->getLocale()),
        ]]);

        return redirect()->route('ads.create.subcategory');
    }

    public function selectSubcategory()
    {
        $adData = session('ad_data', []);

        if (empty($adData['category_id'])) {
            return redirect()->route('ads.create');
        }

        $category = Category::findOrFail($adData['category_id']);

        // Get selected subcategories from session
        $selectedSubcategories = $adData['subcategories'] ?? [];

        // Determine which level we're at
        $currentLevel = count($selectedSubcategories);

        // Get subcategories for current level
        if ($currentLevel === 0) {
            // First level: no parent
            $subcategories = Subcategory::where('category_id', $category->id)
                ->whereNull('parent_subcategory_id')
                ->active()
                ->ordered()
                ->get();
        } else {
            // Get children of last selected subcategory
            $lastSubcategoryId = end($selectedSubcategories)['id'];
            $subcategories = Subcategory::where('parent_subcategory_id', $lastSubcategoryId)
                ->active()
                ->ordered()
                ->get();
        }

        // Get all levels for display (support infinite levels)
        $levels = [];

        // Level 0: First level subcategories (no parent)
        $levels[0] = Subcategory::where('category_id', $category->id)
            ->whereNull('parent_subcategory_id')
            ->active()
            ->ordered()
            ->get();

        // Dynamically load all subsequent levels based on selected subcategories
        for ($i = 0; $i < count($selectedSubcategories); $i++) {
            $parentId = $selectedSubcategories[$i]['id'];
            $levels[$i + 1] = Subcategory::where('parent_subcategory_id', $parentId)
                ->active()
                ->ordered()
                ->get();
        }

        // Also load the next level (current level) if available
        if (count($selectedSubcategories) > 0) {
            $lastSelectedId = end($selectedSubcategories)['id'];
            $nextLevel = count($selectedSubcategories);
            $levels[$nextLevel] = Subcategory::where('parent_subcategory_id', $lastSelectedId)
                ->active()
                ->ordered()
                ->get();
        }

        return view('frontend.ads.create.step2-subcategory', compact('category', 'subcategories', 'selectedSubcategories', 'levels', 'currentLevel'));
    }

    public function processSubcategory(Request $request)
    {
        $adData = session('ad_data', []);

        if (empty($adData['category_id'])) {
            return redirect()->route('ads.create');
        }

        $request->validate([
            'subcategory_id' => 'required|exists:subcategories,id',
        ]);

        $subcategory = Subcategory::findOrFail($request->subcategory_id);

        // Check if this subcategory belongs to the selected category
        if ($subcategory->category_id != $adData['category_id']) {
            return back()->withErrors(['error' => __('frontend.ads.invalid_subcategory')]);
        }

        // Get selected subcategories
        $selectedSubcategories = $adData['subcategories'] ?? [];

        // Determine the level of the selected subcategory
        $subcategoryLevel = 0;
        if ($subcategory->parent_subcategory_id) {
            // Find which level this subcategory is at
            $parent = Subcategory::find($subcategory->parent_subcategory_id);
            if ($parent) {
                // Check if parent is in selected subcategories
                $parentIndex = -1;
                foreach ($selectedSubcategories as $index => $selected) {
                    if ($selected['id'] == $parent->id) {
                        $parentIndex = $index;
                        break;
                    }
                }
                $subcategoryLevel = $parentIndex + 1;
            }
        }

        // If this is level 0 (no parent), reset the array
        if ($subcategoryLevel === 0) {
            $selectedSubcategories = [];
        } else {
            // Remove all subcategories after the parent level
            $selectedSubcategories = array_slice($selectedSubcategories, 0, $subcategoryLevel);
        }

        // Add the new subcategory
        $selectedSubcategories[] = [
            'id' => $subcategory->id,
            'name' => $subcategory->getName(app()->getLocale()),
        ];

        $adData['subcategories'] = $selectedSubcategories;
        session(['ad_data' => $adData]);

        // Check if this subcategory has children
        $hasChildren = Subcategory::where('parent_subcategory_id', $subcategory->id)
            ->active()
            ->exists();

        if ($hasChildren) {
            // Continue to next level
            return redirect()->route('ads.create.subcategory');
        } else {
            // No more levels, go to ad details
            $adData['subcategory_id'] = $subcategory->id;
            session(['ad_data' => $adData]);
            return redirect()->route('ads.create.details');
        }
    }

    public function createDetails()
    {
        $user = Auth::user();
        $adData = session('ad_data', []);

        if (empty($adData['category_id']) || empty($adData['subcategory_id'])) {
            return redirect()->route('ads.create');
        }

        // Check if user can create a free ad
        if (!$user->canCreateFreeAd()) {
            return redirect()->route('packages.index')
                ->with('error', __('frontend.ads.free_ads_limit_reached'));
        }

        $category = Category::findOrFail($adData['category_id']);
        $subcategory = Subcategory::findOrFail($adData['subcategory_id']);

        $customFields = \App\Support\CustomFieldsResolver::resolveActiveFields($category, $subcategory);

        $canFeatured = $user->canCreateFeaturedAd();
        $canUrgent = $user->canCreateUrgentAd();
        $remainingFeatured = $user->getRemainingFeaturedAds();
        $remainingUrgent = $user->getRemainingUrgentAds();

        $adImagesConfig = AdImagesConfig::resolve($category, $subcategory);
        $adImagesMax = AdImagesConfig::resolveMaxImages($category, $subcategory);
        $adVideoMaxDurationSeconds = AdVideoUpload::maxDurationSeconds();
        $adVideoMaxSizeMb = (int) max(1, \App\Models\Setting::get('ad_video_max_size_mb', 50));

        return view('frontend.ads.create.step3-details', compact(
            'category', 'subcategory', 'adData', 'customFields',
            'canFeatured', 'canUrgent', 'remainingFeatured', 'remainingUrgent',
            'adImagesConfig',
            'adImagesMax',
            'adVideoMaxDurationSeconds',
            'adVideoMaxSizeMb'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $adData = session('ad_data', []);

        if (empty($adData['category_id']) || empty($adData['subcategory_id'])) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'session_incomplete',
                'Missing category_id or subcategory_id in session when posting ad',
                ['category_id' => $adData['category_id'] ?? null, 'subcategory_id' => $adData['subcategory_id'] ?? null]
            );

            return redirect()->route('ads.create');
        }

        // Check if user can create a free ad
        if (!$user->canCreateFreeAd()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'free_ads_limit',
                (string) __('frontend.ads.free_ads_limit_reached'),
                [
                    'category_id' => $adData['category_id'],
                    'subcategory_id' => $adData['subcategory_id'],
                    'http_status' => 403,
                ]
            );

            return redirect()->route('packages.index')
                ->with('error', __('frontend.ads.free_ads_limit_reached'));
        }

        $category = Category::findOrFail($adData['category_id']);
        $subcategory = Subcategory::findOrFail($adData['subcategory_id']);
        if ((int) $subcategory->category_id !== (int) $category->id) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'subcategory_mismatch',
                (string) __('frontend.ads.invalid_subcategory'),
                ['category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'http_status' => 422]
            );

            return redirect()->route('ads.create')->with('error', __('frontend.ads.invalid_subcategory'));
        }

        $imgCfg = AdImagesConfig::resolve($category, $subcategory);
        $maxImages = (int) ($imgCfg['max_images'] ?? AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES);

        // Validate featured/urgent if submitted (user must have quota)
        $isFeatured = $request->boolean('is_featured');
        $isUrgent = $request->boolean('is_urgent');
        if ($isFeatured && !$user->canCreateFeaturedAd()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'featured_limit',
                (string) __('frontend.ads.featured_limit_reached'),
                ['category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'http_status' => 422]
            );

            return back()->withErrors(['is_featured' => __('frontend.ads.featured_limit_reached')]);
        }
        if ($isUrgent && !$user->canCreateUrgentAd()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'urgent_limit',
                (string) __('frontend.ads.urgent_limit_reached'),
                ['category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'http_status' => 422]
            );

            return back()->withErrors(['is_urgent' => __('frontend.ads.urgent_limit_reached')]);
        }

        // Build validation rules (الموقع: قواعد موحّدة مع الـ API)
        $rules = array_merge([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SYP,TRY,USD,EUR',
        ], AdLocationPayload::validationRules());
        if ($imgCfg['mode'] === AdImagesConfig::MODE_ADMIN_GALLERY) {
            $rules['gallery_image'] = ['required', 'string', Rule::in($imgCfg['gallery_paths'])];
        } else {
            $rules['images'] = 'required|array|min:1|max:'.$maxImages;
            $rules['images.*'] = 'required|image|mimes:jpeg,png,jpg,webp|max:5120';
        }
        $rules['video'] = 'nullable|file|mimes:mp4,mov,webm|max:'.AdVideoUpload::maxSizeKbForValidator();

        $rules = array_merge(
            $rules,
            \App\Support\CustomFieldValidation::rulesForSchema(
                \App\Support\CustomFieldsResolver::resolveActiveFields($category, $subcategory)
            )
        );

        $validator = Validator::make($request->all(), $rules, [
            'images.max' => __('frontend.ads.images_max_count', ['max' => $maxImages]),
        ]);
        AdLocationPayload::attachLocationConsistency($validator, $request);
        if ($validator->fails()) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'validation_failed',
                'Laravel validation failed on ad store (web)',
                [
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                    'errors' => $validator->errors()->toArray(),
                    'title_preview' => mb_substr((string) $request->input('title', ''), 0, 120),
                ]
            );
            throw new ValidationException($validator);
        }
        $validated = $validator->validated();

        if ($request->hasFile('video')) {
            $vErrs = AdVideoUpload::validate($request->file('video'));
            if ($vErrs !== []) {
                return back()->withErrors(['video' => $vErrs[0]])->withInput();
            }
        }

        if (isset($validated['custom_fields'])) {
            $validated['custom_fields'] = \App\Support\CustomFieldValidation::normalizeStoredValues($validated['custom_fields']);
        } else {
            $validated['custom_fields'] = [];
        }
        $validated['custom_fields'] = \App\Support\SellerTypeField::applyLockedOwner(
            $validated['custom_fields'],
            \App\Support\CustomFieldsResolver::resolveActiveFields($category, $subcategory),
            $user
        );

        // صور الإعلان: رفع أو مسار من معرض لوحة التحكم
        $images = [];
        if ($imgCfg['mode'] === AdImagesConfig::MODE_ADMIN_GALLERY) {
            $images = [(string) $request->input('gallery_image')];
        } else {
            if ($request->hasFile('images')) {
                $storage = \Illuminate\Support\Facades\Storage::disk('public');
                $targetDir = $storage->path('ads/images');
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                }
                foreach ($request->file('images') as $image) {
                    if (! $image->isValid()) {
                        continue;
                    }
                    try {
                        $images[] = store_ad_image_raw($image);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Ad image upload failed', [
                            'file' => $image->getClientOriginalName(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        if ($imgCfg['mode'] !== AdImagesConfig::MODE_ADMIN_GALLERY && $images === []) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'no_valid_images',
                'No images stored after upload (all invalid or processing failed)',
                ['category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'http_status' => 422]
            );

            return back()->withErrors(['images' => __('frontend.ads.images_none_valid')])->withInput();
        }

        $videoPath = null;
        if ($request->hasFile('video')) {
            try {
                $videoPath = store_ad_video_raw($request->file('video'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Ad video upload failed', [
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors(['video' => __('frontend.ads.video_upload_invalid')])->withInput();
            }
        }

        $fieldSchema = CustomFieldsResolver::resolveActiveFields($category, $subcategory);
        $price = $validated['price'] ?? null;
        $currency = $validated['currency'] ?? \App\Models\Setting::get('default_currency', 'SYP');
        $cf = $validated['custom_fields'] ?? [];
        [$syncedPrice, $syncedCurrency] = $this->resolveAdPriceAndCurrencyFromCustomFields(
            $cf,
            $fieldSchema,
            $price,
            $currency
        );
        $price = $syncedPrice;
        $currency = $syncedCurrency;

        $locRow = AdLocationPayload::normalizedForDatabase($validated, $request);

        // Create the ad
        try {
            $ad = Ad::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'price' => $price,
                'currency' => $currency,
                'images' => $images,
                'video' => $videoPath,
                'custom_fields' => $validated['custom_fields'] ?? [],
                'location_country' => $locRow['location_country'],
                'location_state' => $locRow['location_state'],
                'location_state_code' => $locRow['location_state_code'],
                'location_city' => $locRow['location_city'],
                'location_city_code' => $locRow['location_city_code'],
                'location_district' => $locRow['location_district'],
                'location_district_code' => $locRow['location_district_code'],
                'location_address' => $locRow['location_address'],
                'location_input_method' => $locRow['location_input_method'],
                'show_location' => $locRow['show_location'],
                'latitude' => $locRow['latitude'],
                'longitude' => $locRow['longitude'],
                'status' => 'pending', // Will be approved by admin
                'published_at' => now(),
                'is_featured' => $isFeatured,
                'is_urgent' => $isUrgent,
            ]);
        } catch (\Throwable $e) {
            AdPublishFailureLogger::log(
                AdPublishFailureLogger::SOURCE_WEB,
                $request,
                'database_exception',
                $e->getMessage(),
                [
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                    'exception_class' => get_class($e),
                    'http_status' => 500,
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

        // Log activity
        \App\Models\UserActivityLog::log(
            'ad_created',
            __('frontend.profile.activity.ad_created_description', ['title' => $ad->title]),
            $ad,
            ['ad_id' => $ad->id, 'ad_title' => $ad->title, 'category' => $category->getName(app()->getLocale())]
        );

        // Clear session
        session()->forget('ad_data');

        if ($imgCfg['mode'] !== AdImagesConfig::MODE_ADMIN_GALLERY && $images !== []) {
            ProcessAdImagesJob::dispatch($ad->id, $images)->afterResponse();
        }

        return redirect()->route('home')
            ->with('success', __('frontend.ads.ad_created_successfully'));
    }

    /**
     * @param  array<string, mixed>  $customFields
     * @param  array<int, array<string, mixed>>  $fieldSchema
     * @return array{0: mixed, 1: string}
     */
    private function resolveAdPriceAndCurrencyFromCustomFields(
        array $customFields,
        array $fieldSchema,
        mixed $fallbackPrice,
        string $fallbackCurrency
    ): array {
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($fieldSchema);

        if ($priceFieldId && $customFields !== []) {
            $extracted = CustomFieldsFilterSupport::extractPriceAndCurrencyFromCustomFields(
                $customFields,
                $priceFieldId,
                $fallbackCurrency
            );
            if ($extracted['price'] !== null) {
                return [$extracted['price'], $extracted['currency'] ?? $fallbackCurrency];
            }
        }

        if ($customFields !== []) {
            $extracted = CustomFieldsFilterSupport::extractPriceAndCurrencyFromCustomFields(
                $customFields,
                null,
                $fallbackCurrency
            );
            if ($extracted['price'] !== null) {
                return [$extracted['price'], $extracted['currency'] ?? $fallbackCurrency];
            }
        }

        if ($fallbackPrice !== null && $fallbackPrice !== '') {
            return [$fallbackPrice, $fallbackCurrency];
        }

        return [null, $fallbackCurrency];
    }
}
