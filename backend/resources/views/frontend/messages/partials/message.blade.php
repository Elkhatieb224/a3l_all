<div class="flex {{ $message->sender_id === Auth::id() ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
    <div class="max-w-xs sm:max-w-md lg:max-w-lg">
        <div class="flex items-start gap-2 {{ $message->sender_id === Auth::id() ? 'flex-row-reverse' : 'flex-row' }}">
            @if($message->sender_id !== Auth::id())
                <img src="{{ $message->sender->avatar ? asset('storage/' . $message->sender->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($message->sender->name) }}"
                     alt="{{ $message->sender->name }}"
                     class="w-8 h-8 rounded-full flex-shrink-0">
            @endif
            <div class="flex-1">
                <div class="bg-{{ $message->sender_id === Auth::id() ? 'primary text-white' : 'gray-100 text-gray-800' }} rounded-lg p-3">
                    @if(!empty($message->message))
                        <p class="text-sm whitespace-pre-wrap">{{ $message->message }}</p>
                    @endif
                    
                    @if($message->attachments && count($message->attachments) > 0)
                        <div class="mt-2 space-y-2">
                            @foreach($message->attachments as $attachment)
                                @php
                                    $mime = $attachment['mime'] ?? '';
                                    $isImage = strpos($mime, 'image/') === 0;
                                @endphp
                                <div class="flex items-center gap-2 p-2 {{ $message->sender_id === Auth::id() ? 'bg-white bg-opacity-20' : 'bg-gray-200' }} rounded">
                                    @if($isImage)
                                        <a href="{{ asset('storage/' . $attachment['path']) }}" target="_blank" class="block">
                                            <img src="{{ asset('storage/' . $attachment['path']) }}" 
                                                 alt="{{ $attachment['name'] ?? 'Image' }}"
                                                 class="max-w-full h-auto rounded max-h-48">
                                        </a>
                                    @else
                                        <a href="{{ asset('storage/' . $attachment['path']) }}" 
                                           download="{{ $attachment['name'] ?? 'file' }}"
                                           class="flex items-center gap-2 hover:opacity-80 transition">
                                            <i class="fas fa-file {{ $message->sender_id === Auth::id() ? 'text-white' : 'text-gray-600' }}"></i>
                                            <span class="text-sm truncate">{{ $attachment['name'] ?? 'File' }}</span>
                                            <span class="text-xs opacity-75">({{ number_format(($attachment['size'] ?? 0) / 1024, 2) }} KB)</span>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-1 {{ $message->sender_id === Auth::id() ? 'text-left' : 'text-right' }}">
                    {{ $message->created_at->format('H:i') }}
                </p>
            </div>
        </div>
    </div>
</div>

