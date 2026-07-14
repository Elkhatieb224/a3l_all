<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->getName($locale),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name_tr' => $this->name_tr,
            'description' => $this->getDescription($locale),
            'price' => $this->price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
            'ads_limit' => $this->ads_limit,
            'featured_ads' => $this->featured_ads,
            'urgent_ads' => $this->urgent_ads,
            'priority_support' => $this->priority_support,
            'homepage_display' => $this->homepage_display,
            'features' => $this->features ?? [],
            'is_active' => $this->is_active,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
