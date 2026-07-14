<?php

namespace App\Http\Resources;

use App\Support\AdImagesConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{

    public static function imageToFullUrl($image, ?Request $request = null): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }
        $path = is_array($image) ? ($image['path'] ?? $image['url'] ?? $image['src'] ?? null) : $image;
        if ($path === null || (is_string($path) && trim($path) === '')) {
            return null;
        }
        $path = trim((string) $path);
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return AdImagesConfig::storageUrlForPath($path, $request) ?: null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $imagesRaw = $this->images ?? [];
        $images = collect($imagesRaw)
            ->map(fn ($img) => static::imageToFullUrl($img, $request))
            ->filter()
            ->values()
            ->toArray();
        $firstImage = $images[0] ?? null;

        $formattedPrice = $this->display_price;
        $price = $this->price !== null && $this->price !== '' ? (is_numeric($this->price) ? (float) $this->price : $this->price) : null;
        $currency = $this->currency ?? null;
        if ($price === null && !empty($this->custom_fields)) {
            foreach (['price', 'salary'] as $key) {
                if (!isset($this->custom_fields[$key])) continue;
                $v = $this->custom_fields[$key];
                if (is_array($v) && isset($v['value']) && ($v['value'] !== '' && $v['value'] !== null)) {
                    $price = is_numeric($v['value']) ? (float) $v['value'] : $v['value'];
                    $currency = $v['currency'] ?? $currency;
                    break;
                }
                if (is_numeric($v) && (string)$v !== '') {
                    $price = (float) $v;
                    break;
                }
            }
        }

        $isOwner = $user ? (int) $this->user_id === (int) $user->id : false;
        $showLocationText = ($this->show_location ?? true) || $isOwner;

        $videoPath = is_string($this->video ?? null) ? trim((string) $this->video) : '';
        $videoUrl = $videoPath !== '' ? static::imageToFullUrl($videoPath, $request) : null;

        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $price,
            'currency' => $currency,
            'formatted_price' => $formattedPrice,
            'price_type' => $this->price_type,
            'images' => $images,
            'first_image' => $firstImage,
            'image' => $firstImage,
            'video' => $videoUrl,
            'has_video' => $videoUrl !== null,
            'custom_fields' => $this->custom_fields ?? [],
            'status' => $this->status,
            'pending_changes' => $this->pending_changes ?? null,
            'is_featured' => $this->is_featured,
            'is_urgent' => $this->is_urgent,
            'views_count' => $this->views_count,
            'messages_count' => $this->conversations_count ?? 0,
            'favorites_count' => $this->favorites_count ?? 0,
            'location_country' => $showLocationText ? $this->location_country : null,
            'location_state' => $showLocationText ? $this->location_state : null,
            'location_state_code' => $showLocationText ? $this->location_state_code : null,
            'location_city' => $showLocationText ? $this->location_city : null,
            'location_city_code' => $showLocationText ? $this->location_city_code : null,
            'location_district' => $showLocationText ? $this->location_district : null,
            'location_district_code' => $showLocationText ? $this->location_district_code : null,
            'location_address' => $showLocationText ? $this->location_address : null,
            'location_input_method' => $this->location_input_method,
            'show_location' => (bool) ($this->show_location ?? true),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category', function() {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->getName(app()->getLocale()),
                    'slug' => $this->category->slug,
                    'custom_fields' => $this->category->custom_fields ?? [],
                ];
            }),
            'subcategory' => $this->whenLoaded('subcategory', function() {
                return [
                    'id' => $this->subcategory->id,
                    'name' => $this->subcategory->getName(app()->getLocale()),
                    'slug' => $this->subcategory->slug,
                    'custom_fields' => $this->subcategory->custom_fields ?? [],
                ];
            }),
            'category_path' => $this->when($this->relationLoaded('category') || $this->relationLoaded('subcategory'), function () {
                $locale = app()->getLocale();
                $path = [];
                if ($this->category) {
                    $path[] = $this->category->getName($locale);
                }
                $sub = $this->subcategory;
                $chain = [];
                while ($sub) {
                    array_unshift($chain, $sub->getName($locale));
                    $sub = $sub->parent ?? null;
                }
                return array_merge($path, $chain);
            }),
            'category_path_segments' => $this->buildCategoryPathSegments(),
            'user' => $this->whenLoaded('user', function () use ($request) {
                $avatarPath = $this->user->avatar;
                $avatarUrl = $avatarPath
                    ? AdImagesConfig::storageUrlForPath($avatarPath, $request)
                    : null;
                $displayName = ($this->user->is_verified && !empty($this->user->business_name))
                    ? $this->user->business_name
                    : $this->user->name;
                return [
                    'id' => $this->user->id,
                    'name' => $displayName,
                    'slug' => $this->user->slug,
                    'avatar' => $avatarUrl,
                    'is_verified' => $this->user->is_verified,
                    'phone' => $this->user->phone,
                    'country_code' => $this->user->country_code,
                    'location_country' => $this->user->location_country,
                ];
            }),
            'is_favorite' => $user
                ? (bool) ($this->resource->is_favorite ?? $this->isFavoriteBy($user->id))
                : false,
            'is_owner' => $isOwner,
            'can_negotiate_price' => (bool) ($this->category?->enable_negotiation ?? true),
        ];
    }

    /**
     * @return list<array{type: string, id: int, slug: string, name: string}>
     */
    protected function buildCategoryPathSegments(): array
    {
        /** @var \App\Models\Ad $ad */
        $ad = $this->resource;
        $locale = app()->getLocale();
        $segments = [];

        if ($ad->category) {
            $segments[] = [
                'type' => 'category',
                'id' => (int) $ad->category->id,
                'slug' => (string) $ad->category->slug,
                'name' => $ad->category->getName($locale),
            ];
        }

        $sub = $ad->subcategory;
        $chain = [];
        while ($sub) {
            array_unshift($chain, $sub);
            $sub = $sub->parent ?? null;
        }

        foreach ($chain as $node) {
            if (! is_object($node)) {
                continue;
            }
            $segments[] = [
                'type' => 'subcategory',
                'id' => (int) $node->id,
                'slug' => (string) $node->slug,
                'name' => $node->getName($locale),
            ];
        }

        return $segments;
    }
}
