<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'message' => $this->message,
            'attachments' => collect($this->attachments ?? [])->map(function($attachment) {
                return [
                    'name' => $attachment['name'] ?? basename($attachment),
                    'url' => asset('storage/' . ($attachment['path'] ?? $attachment)),
                    'type' => $attachment['type'] ?? 'file',
                ];
            })->toArray(),
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sender' => $this->whenLoaded('sender', function() {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'avatar' => $this->sender->avatar ? asset('storage/' . $this->sender->avatar) : null,
                    'is_verified' => $this->sender->is_verified,
                ];
            }),
        ];
    }
}
