<?php

namespace App\Http\Resources;

use App\Support\AdImagesConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $otherUser = $this->sender_id === $user->id ? $this->receiver : $this->sender;

        $unreadCount = (int) ($this->resource->getAttribute('api_unread_messages_count') ?? 0);

        $otherUserId = $otherUser?->id;
        $blockedLookup = $request->attributes->get('blocked_user_ids_lookup', []);
        $isOtherUserBlocked = $otherUserId && is_array($blockedLookup)
            ? isset($blockedLookup[$otherUserId])
            : ($otherUserId ? $user->hasBlocked($otherUserId) : false);

        return [
            'id' => $this->id,
            'ad_id' => $this->ad_id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'last_message_at' => $this->last_message_at,
            'unread_count' => $unreadCount,
            'is_other_user_blocked' => $isOtherUserBlocked,
            'ad' => $this->whenLoaded('ad', function () use ($request, $user) {
                $imageUrls = collect($this->ad->images ?? [])
                    ->map(fn ($img) => AdResource::imageToFullUrl($img, $request))
                    ->filter()
                    ->values()
                    ->toArray();
                $firstImage = $imageUrls[0] ?? null;
                $isAdOwner = $user && (int) $this->ad->user_id === (int) $user->id;
                $adData = [
                    'id' => $this->ad->id,
                    'uid' => $this->ad->uid,
                    'title' => $this->ad->title,
                    'price' => $this->ad->price,
                    'currency' => $this->ad->currency,
                    'formatted_price' => $this->ad->price ? format_price($this->ad->price, 2, $this->ad->currency) : null,
                    'images' => $imageUrls,
                    'first_image' => $firstImage,
                    'image' => $firstImage,
                    'location_state' => $this->ad->location_state,
                    'location_city' => $this->ad->location_city,
                    'location_district' => $this->ad->location_district,
                    'category' => $this->ad->category ? ['name' => $this->ad->category->getName(app()->getLocale())] : null,
                    'subcategory' => $this->ad->subcategory ? ['name' => $this->ad->subcategory->getName(app()->getLocale())] : null,
                    'views_count' => (int) ($this->ad->views_count ?? 0),
                    'published_at' => $this->ad->published_at?->toIso8601String(),
                ];
                if ($isAdOwner) {
                    $adData['messages_count'] = (int) ($this->ad->conversations_count ?? 0);
                    $adData['favorites_count'] = (int) ($this->ad->favorites_count ?? 0);
                }
                $adData['is_ad_owner'] = $isAdOwner;
                return $adData;
            }),
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'slug' => $otherUser->slug,
                'avatar' => $otherUser->avatar
                    ? AdImagesConfig::storageUrlForPath($otherUser->avatar, $request)
                    : null,
                'is_verified' => $otherUser->is_verified,
            ] : null,
            'latest_message' => $this->whenLoaded('latestMessage', function() {
                return $this->latestMessage ? [
                    'id' => $this->latestMessage->id,
                    'message' => $this->latestMessage->message,
                    'sender_id' => $this->latestMessage->sender_id,
                    'created_at' => $this->latestMessage->created_at,
                    'is_read' => $this->latestMessage->is_read,
                    'read_at' => $this->latestMessage->read_at,
                ] : null;
            }),
        ];
    }
}
