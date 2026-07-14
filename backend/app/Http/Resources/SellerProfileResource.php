<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SellerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $displayName = $this->business_name ?? $this->name;
        $displayPhone = $this->business_phone ?? $this->phone;

        $currentUserId = Auth::guard('sanctum')->id();

        return [
            'id' => $this->id,
            'name' => $displayName,
            'slug' => $this->slug,
            'avatar' => $this->avatar ? (str_starts_with(trim($this->avatar), 'http') ? $this->avatar : asset('storage/' . ltrim(trim($this->avatar), '/'))) : null,
            'bio' => $this->bio,
            'business_type' => $this->business_type,
            'location_country' => $this->location_country,
            'location_city' => $this->location_city,
            'business_address' => $this->business_address,
            'phone' => $displayPhone,
            'country_code' => $this->country_code,
            'is_verified' => $this->is_verified,
            'storefront_image_path' => $this->storefront_image_path ? (str_starts_with(trim($this->storefront_image_path), 'http') ? $this->storefront_image_path : asset('storage/' . ltrim(trim($this->storefront_image_path), '/'))) : null,
            'instagram_url' => $this->instagram_url,
            'facebook_url' => $this->facebook_url,
            'website_url' => $this->website_url,
            'ads_count' => $this->ads_count ?? $this->ads()->count(),
            'average_rating' => round($this->ratingsAsSeller()->avg('rating') ?? 0, 1),
            'ratings_count' => $this->ratingsAsSeller()->count(),
            'followers_count' => $this->favoritedBy()->count(),
            'following_count' => $this->favoriteSellers()->count(),
            'is_me' => $currentUserId ? $currentUserId === $this->id : false,
            'member_since' => $this->created_at?->format('Y'),
            'created_at' => $this->created_at,
        ];
    }
}
