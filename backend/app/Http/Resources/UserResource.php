<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'business_owner' => $this->business_owner,
            'business_address' => $this->business_address,
            'business_phone' => $this->business_phone,
            'instagram_url' => $this->instagram_url,
            'facebook_url' => $this->facebook_url,
            'website_url' => $this->website_url,
            'storefront_image' => $this->storefront_image_path
                ? Storage::disk('public')->url($this->storefront_image_path)
                : null,
            'avatar' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'bio' => $this->bio,
            'location_country' => $this->location_country,
            'location_city' => $this->location_city,
            'location_district' => $this->location_district,
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'remaining_free_ads' => $this->getRemainingFreeAds(),
            'has_active_subscription' => $this->activeSubscription ? true : false,
        ];
    }
}
