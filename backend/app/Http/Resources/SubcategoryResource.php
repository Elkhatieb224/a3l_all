<?php

namespace App\Http\Resources;

use App\Support\AdImagesConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $galleryPaths = $this->ad_gallery_images ?? [];
        if (! is_array($galleryPaths)) {
            $galleryPaths = [];
        }
        $galleryPaths = array_values(array_filter(array_map('strval', $galleryPaths)));

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'parent_subcategory_id' => $this->parent_subcategory_id,
            'name' => $this->getName($locale),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name_tr' => $this->name_tr,
            'icon' => $this->icon ? AdImagesConfig::storageUrlForPath($this->icon, $request) : null,
            'image' => $this->image ? AdImagesConfig::storageUrlForPath($this->image, $request) : null,
            'is_active' => $this->is_active,
            'ad_images_mode' => $this->ad_images_mode,
            'ad_images_max' => $this->ad_images_max,
            'ad_gallery_paths' => $galleryPaths,
            'ad_gallery_urls' => AdImagesConfig::galleryPathsToUrls($galleryPaths, $request),
            'custom_fields' => $this->custom_fields ?? [],
            'level' => $this->parent_subcategory_id ? 1 : 0,
            'children' => SubcategoryResource::collection($this->whenLoaded('children')),
            'ads_count' => isset($this->ads_count) ? (int) $this->ads_count : 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
