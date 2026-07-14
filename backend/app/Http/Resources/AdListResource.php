<?php

namespace App\Http\Resources;

use App\Support\AdImagesConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdListResource extends JsonResource
{
    /**
     * مسارات ملفات الصور: سلاسل نصية أو عناصر تحوي path/url.
     *
     * @param  mixed  $images
     * @return list<string>
     */
    private static function normalizeImagePaths($images): array
    {
        if (! is_array($images)) {
            return [];
        }
        $paths = [];
        foreach (array_values($images) as $p) {
            if (is_string($p) && $p !== '') {
                $paths[] = $p;

                continue;
            }
            if (is_array($p)) {
                $path = $p['path'] ?? $p['url'] ?? $p['image'] ?? $p['src'] ?? null;
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        return $paths;
    }

    /**
     * أول كائن موقع من الحقول المخصصة (Location: address / lat / lng).
     *
     * @return array{address: ?string, lat: mixed, lng: mixed}|null
     */
    private function firstCustomFieldLocation(): ?array
    {
        $cf = $this->custom_fields ?? [];
        if (! is_array($cf)) {
            return null;
        }
        foreach ($cf as $value) {
            if (! is_array($value)) {
                continue;
            }
            $m = $value;
            if (isset($m['value']) && is_array($m['value'])) {
                $m = $m['value'];
            }
            $lat = $m['latitude'] ?? $m['lat'] ?? null;
            $lng = $m['longitude'] ?? $m['lng'] ?? null;
            $addr = $m['address'] ?? null;
            $hasCoords = $lat !== null && $lng !== null && $lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng);
            $hasAddr = is_string($addr) && trim($addr) !== '';
            if ($hasCoords || $hasAddr) {
                return [
                    'address' => $hasAddr ? trim($addr) : null,
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            }
        }

        return null;
    }

    /**
     * حقول الموقع المعروضة: أعمدة الإعلان، مع التكميل من custom_fields عند الحاجة (مثل صفحات الويب).
     *
     * @return array{country: ?string, state: ?string, city: ?string, district: ?string, address: ?string}
     */
    private function effectiveLocationFields(): array
    {
        if (! (bool) ($this->show_location ?? true)) {
            return [
                'country' => null,
                'state' => null,
                'city' => null,
                'district' => null,
                'address' => null,
            ];
        }

        $country = is_string($this->location_country) && trim($this->location_country) !== ''
            ? trim($this->location_country) : null;
        $state = is_string($this->location_state) && trim($this->location_state) !== ''
            ? trim($this->location_state) : null;
        $city = is_string($this->location_city) && trim($this->location_city) !== ''
            ? trim($this->location_city) : null;
        $district = is_string($this->location_district) && trim($this->location_district) !== ''
            ? trim($this->location_district) : null;
        $address = is_string($this->location_address) && trim($this->location_address) !== ''
            ? trim($this->location_address) : null;

        $cfLoc = $this->firstCustomFieldLocation();
        if ($cfLoc !== null) {
            if ($address === null && $cfLoc['address'] !== null) {
                $address = $cfLoc['address'];
            }
        }

        return [
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'district' => $district,
            'address' => $address,
        ];
    }

    /**
     * نص قصير للموقع: دولة - مدينة - منطقة، أو العنوان (من العمود أو من الحقول المخصصة).
     * لا نكتفي برمز دولة من حرفين (مثل SY) إذا وُجد عنوانٌ أو سطرٌ أوضح — مثل بطاقات الويب وبقية واجهات API.
     */
    private function shortLocationLabel(): ?string
    {
        if (! (bool) ($this->show_location ?? true)) {
            return null;
        }

        $loc = $this->effectiveLocationFields();
        $parts = [];
        foreach ([$loc['country'], $loc['state'], $loc['city'], $loc['district']] as $s) {
            if (is_string($s) && trim($s) !== '') {
                $parts[] = trim($s);
            }
        }
        $parts = array_values(array_unique($parts));
        $fromRegions = $parts !== [] ? implode(' - ', $parts) : null;

        $trimAddress = static function (?string $addr): ?string {
            if ($addr === null) {
                return null;
            }
            $oneLine = preg_replace('/\s+/u', ' ', trim($addr));

            return ($oneLine !== '' && $oneLine !== '0') ? $oneLine : null;
        };

        $formatAddr = static function (?string $a): ?string {
            if ($a === null) {
                return null;
            }
            $t = preg_replace('/\s+/u', ' ', $a);

            return $t !== '' ? (mb_strlen($t) > 120 ? mb_substr($t, 0, 117).'…' : $t) : null;
        };

        $addrFmt = $formatAddr($trimAddress($loc['address'] ?? null));

        $onlyIsoCountry = $parts !== [] && count($parts) === 1
            && preg_match('/^[A-Za-z]{2}$/', $parts[0]) === 1;

        if ($addrFmt !== null && ($fromRegions === null || $fromRegions === '' || $onlyIsoCountry)) {
            return $addrFmt;
        }
        if ($fromRegions !== null && $fromRegions !== '') {
            return $fromRegions;
        }
        if ($addrFmt !== null) {
            return $addrFmt;
        }
        if ($loc['country'] !== null && trim($loc['country']) !== '') {
            return trim($loc['country']);
        }

        return null;
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function resolveMapCoordinates(): array
    {
        $lat = $this->latitude;
        $lng = $this->longitude;
        if ($lat !== null && $lat !== '' && is_numeric($lat)) {
            $lat = (float) $lat;
        } else {
            $lat = null;
        }
        if ($lng !== null && $lng !== '' && is_numeric($lng)) {
            $lng = (float) $lng;
        } else {
            $lng = null;
        }
        if ($lat !== null && $lng !== null) {
            return [$lat, $lng];
        }

        $cf = $this->custom_fields ?? [];
        if (! is_array($cf)) {
            return [null, null];
        }
        foreach ($cf as $value) {
            if (! is_array($value)) {
                continue;
            }
            $m = $value;
            if (isset($m['value']) && is_array($m['value'])) {
                $m = $m['value'];
            }
            $la = $m['latitude'] ?? $m['lat'] ?? null;
            $ln = $m['longitude'] ?? $m['lng'] ?? null;
            if ($la !== null && $ln !== null && $la !== '' && $ln !== '' && is_numeric($la) && is_numeric($ln)) {
                return [(float) $la, (float) $ln];
            }
        }

        return [null, null];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paths = self::normalizeImagePaths($this->images ?? []);
        $imageUrls = array_map(
            fn (string $p) => AdImagesConfig::storageUrlForPath($p, $request),
            array_slice($paths, 0, 4)
        );
        $thumbnail = $imageUrls[0] ?? null;

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

        [$mapLat, $mapLng] = $this->resolveMapCoordinates();
        $effLoc = $this->effectiveLocationFields();

        $hasVideo = is_string($this->video ?? null) && trim((string) $this->video) !== '';

        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'title' => $this->title,
            'price' => $price,
            'currency' => $currency,
            'formatted_price' => $formattedPrice,
            'thumbnail' => $thumbnail,
            'images' => $imageUrls,
            'has_video' => $hasVideo,
            'location_short' => $this->shortLocationLabel(),
            'location_country' => $effLoc['country'],
            'location_state' => $effLoc['state'],
            'location_city' => $effLoc['city'],
            'location_district' => $effLoc['district'],
            'location_address' => $effLoc['address'],
            'show_location' => (bool) ($this->show_location ?? true),
            'latitude' => $mapLat,
            'longitude' => $mapLng,
            'is_featured' => (bool) $this->is_featured,
            'is_urgent' => (bool) $this->is_urgent,
            'is_favorite' => isset($this->is_favorite) ? (bool) $this->is_favorite : false,
            'views_count' => (int) ($this->views_count ?? 0),
            'messages_count' => (int) ($this->conversations_count ?? 0),
            'favorites_count' => (int) ($this->favorites_count ?? 0),
            'published_at' => $this->published_at,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->getName(app()->getLocale()),
                ];
            }),
            'subcategory' => $this->whenLoaded('subcategory', function () {
                return [
                    'id' => $this->subcategory->id,
                    'name' => $this->subcategory->getName(app()->getLocale()),
                ];
            }),
            'user' => $this->whenLoaded('user', function () use ($request) {
                $displayName = ($this->user->is_verified && !empty($this->user->business_name))
                    ? $this->user->business_name
                    : $this->user->name;
                return [
                    'id' => $this->user->id,
                    'name' => $displayName,
                    'slug' => $this->user->slug,
                    'avatar' => $this->user->avatar
                        ? AdImagesConfig::storageUrlForPath($this->user->avatar, $request)
                        : null,
                    'is_verified' => $this->user->is_verified,
                ];
            }),
        ];
    }
}
