@extends('frontend.layouts.app')

@section('title', __('frontend.messages.conversation'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md">
                    <!-- Header -->
                    <div class="p-4 sm:p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('messages.index') }}" 
                                   class="text-gray-600 hover:text-primary">
                                    <i class="fas fa-arrow-right text-xl"></i>
                                </a>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">{{ $conversation->ad?->title ?? __('frontend.messages.chat_with_seller') }}</h2>
                                    <p class="text-sm text-gray-600">
                                        {{ $conversation->sender_id === Auth::id() ? $conversation->receiver->name : $conversation->sender->name }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('profile.reports.create', ['conversation_id' => $conversation->id]) }}" 
                                   class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-sm transition">
                                    <i class="fas fa-flag ml-2"></i>
                                    {{ __('frontend.messages.report_conversation') }}
                                </a>
                                @if($conversation->ad)
                                    <a href="{{ route('ads.show', $conversation->ad->uid) }}"
                                       class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-sm transition">
                                        <i class="fas fa-external-link-alt ml-2"></i>
                                        {{ __('frontend.messages.view_ad') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Messaging Rules Warning -->
                    @php
                        $locale = app()->getLocale();
                        $rulesKey = 'messaging_rules_' . $locale;
                        $rulesText = \App\Models\Setting::get($rulesKey, '');
                        $helpCenterLink = route('help.index');
                        // Show rules in all conversations if text exists
                        $showRules = $rulesText && strlen(trim(strip_tags($rulesText))) > 0;
                    @endphp
                    @if($showRules)
                        <div class="p-4 sm:p-6 border-b border-gray-200 bg-gray-50">
                            <div class="bg-amber-50 border-r-4 border-amber-500 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 text-amber-600">
                                        <i class="fas fa-exclamation-triangle text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ __('frontend.messages.important_alert') }}</h4>
                                        <div class="text-xs text-gray-700 space-y-2">
                                            {!! $rulesText !!}
                                        </div>
                                        <p class="text-xs text-gray-600 mt-3">
                                            {{ __('frontend.messages.detailed_info_help_center') }}
                                            <a href="{{ $helpCenterLink }}" class="text-primary hover:text-secondary underline font-semibold">
                                                {{ __('frontend.help.title') }}
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Messages -->
                    <div class="p-4 sm:p-6 h-96 overflow-y-auto space-y-4" id="messages-container" data-conversation-id="{{ $conversation->id }}">
                        <!-- Ad Card - Show at the beginning of conversation -->
                        @if($conversation->ad)
                            @php
                                $ad = $conversation->ad;
                                $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                                $images = $images ?? [];
                                $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                                $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                                $adImage = $firstImagePath ? asset('storage/' . $firstImagePath) : asset('images/no-image.png');
                                $adUrl = route('ads.show', $ad->uid);
                                $categoryPath = '';
                                if($ad->subcategory) {
                                    $categoryPath = $ad->subcategory->getName(app()->getLocale());
                                    if($ad->subcategory->parent) {
                                        $categoryPath = $ad->subcategory->parent->getName(app()->getLocale()) . ' > ' . $categoryPath;
                                    }
                                }
                                $location = '';
                                if($ad->location_city) {
                                    $location = $ad->location_city;
                                    if($ad->location_district) {
                                        $location .= ' / ' . $ad->location_district;
                                    }
                                }
                            @endphp
                            <a href="{{ $adUrl }}" class="block bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow mb-4">
                                <div class="flex gap-4 p-4">
                                    <!-- Ad Image -->
                                    <div class="w-24 h-24 flex-shrink-0">
                                        <img src="{{ $adImage }}" 
                                             alt="{{ $ad->title }}"
                                             class="w-full h-full object-cover rounded-lg">
                                    </div>
                                    
                                    <!-- Ad Details -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-primary font-semibold text-sm mb-1 line-clamp-2 hover:text-secondary transition">
                                            {{ $ad->title }}
                                        </h3>
                                        
                                        @if($categoryPath)
                                            <p class="text-xs text-gray-500 mb-1 truncate">
                                                {{ $categoryPath }}
                                            </p>
                                        @endif
                                        
                                        @if($location)
                                            <p class="text-xs text-gray-500 mb-2 truncate">
                                                <i class="fas fa-map-marker-alt ml-1"></i>
                                                {{ $location }}
                                            </p>
                                        @endif
                                        
                                        <div class="flex items-center justify-between">
                                            @if($ad->display_price)
                                                <p class="text-red-600 font-bold text-lg">
                                                    {{ $ad->display_price }}
                                                </p>
                                            @endif
                                            
                                            <!-- Ad Number -->
                                            @if($ad->uid)
                                                <div class="text-xs text-gray-400">
                                                    {{ __('frontend.profile.my_ads_management.uid') }}: {{ $ad->uid }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endif
                        
                        @foreach($messages as $message)
                            @include('frontend.messages.partials.message', ['message' => $message])
                        @endforeach
                    </div>

                    <!-- Message Form -->
                    <div class="p-4 sm:p-6 border-t border-gray-200">
                        <form id="message-form" action="{{ route('messages.store', $conversation->id) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                            @csrf
                            
                            <!-- File Attachments Preview -->
                            <div id="attachments-preview" class="hidden flex-wrap gap-2 mb-2"></div>
                            
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <textarea name="message" 
                                              id="message-input"
                                              rows="1"
                                              placeholder="{{ __('frontend.messages.type_message') }}"
                                              class="w-full px-4 py-3 pr-20 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none"
                                              onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(); }"></textarea>
                                    
                                    <!-- File Input -->
                                    <label for="file-input" class="absolute {{ app()->getLocale() === 'ar' ? 'left-2' : 'right-2' }} top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 hover:text-primary transition">
                                        <i class="fas fa-paperclip text-xl"></i>
                                        <input type="file" 
                                               id="file-input" 
                                               name="attachments[]" 
                                               multiple 
                                               accept="image/*,.pdf,.doc,.docx,.txt"
                                               class="hidden"
                                               onchange="handleFileSelect(event)">
                                    </label>
                                </div>
                                
                                <button type="button" 
                                        onclick="sendMessage()" 
                                        id="send-btn"
                                        class="btn-primary px-6 py-3 rounded-lg whitespace-nowrap">
                                    <i class="fas fa-paper-plane ml-2"></i>
                                    {{ __('frontend.messages.send') }}
                                </button>
                            </div>
                            
                            <p class="text-xs text-gray-500">
                                {{ __('frontend.messages.file_hint') }}
                            </p>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
let selectedFiles = [];
let lastMessageId = {{ $messages->last()->id ?? 0 }};
let pollingInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
    
    // Start polling for new messages
    startPolling();
    
    // Auto-resize textarea
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});

function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    selectedFiles = [...selectedFiles, ...files];
    updateAttachmentsPreview();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateAttachmentsPreview();
    // Reset file input
    document.getElementById('file-input').value = '';
}

function updateAttachmentsPreview() {
    const preview = document.getElementById('attachments-preview');
    if (selectedFiles.length === 0) {
        preview.classList.add('hidden');
        return;
    }
    
    preview.classList.remove('hidden');
    preview.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 bg-gray-100 rounded-lg p-2 text-sm';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'w-12 h-12 object-cover rounded';
            div.appendChild(img);
        } else {
            const icon = document.createElement('i');
            icon.className = 'fas fa-file text-gray-600';
            div.appendChild(icon);
        }
        
        const name = document.createElement('span');
        name.className = 'truncate max-w-xs';
        name.textContent = file.name;
        div.appendChild(name);
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.onclick = () => removeFile(index);
        removeBtn.className = 'text-red-500 hover:text-red-700 ml-2';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        div.appendChild(removeBtn);
        
        preview.appendChild(div);
    });
}

function sendMessage() {
    const form = document.getElementById('message-form');
    const formData = new FormData();
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const container = document.getElementById('messages-container');
    
    // Add CSRF token
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    
    // Add message
    if (messageInput.value.trim()) {
        formData.append('message', messageInput.value.trim());
    }
    
    // Add selected files (only from selectedFiles array, not from input)
    selectedFiles.forEach(file => {
        formData.append('attachments[]', file);
    });
    
    // Disable form
    sendBtn.disabled = true;
    messageInput.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> {{ __('frontend.messages.sending') }}';
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to container
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
            container.appendChild(tempDiv.firstElementChild);
            
            // Update last message ID
            lastMessageId = data.message.id;
            
            // Clear form
            messageInput.value = '';
            messageInput.style.height = 'auto';
            selectedFiles = [];
            updateAttachmentsPreview();
            // Clear file input
            document.getElementById('file-input').value = '';
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        } else {
            alert(data.message || '{{ __('frontend.messages.send_error') }}');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('{{ __('frontend.messages.send_error') }}');
    })
    .finally(() => {
        sendBtn.disabled = false;
        messageInput.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane ml-2"></i> {{ __('frontend.messages.send') }}';
    });
}

function startPolling() {
    const conversationId = document.getElementById('messages-container').dataset.conversationId;
    
    pollingInterval = setInterval(() => {
        fetch(`{{ route('messages.show', $conversation->id) }}?last_id=${lastMessageId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                const container = document.getElementById('messages-container');
                data.messages.forEach(message => {
                    if (message.sender_id !== {{ Auth::id() }}) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = message.html;
                        container.appendChild(tempDiv.firstElementChild);
                        lastMessageId = message.id;
                    }
                });
                
                // Scroll to bottom if user is near bottom
                const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
                if (isNearBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        })
        .catch(error => {
            console.error('Polling error:', error);
        });
    }, 3000); // Poll every 3 seconds
}

// Stop polling when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    } else {
        if (!pollingInterval) {
            startPolling();
        }
    }
});
</script>
@endsection

