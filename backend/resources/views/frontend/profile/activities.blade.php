@extends('frontend.layouts.app')

@section('title', __('frontend.profile.account_activities'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.account_activities') }}
                    </h1>

                    @if($activities->count() > 0)
                        <div class="space-y-3">
                            @foreach($activities as $activity)
                                @php
                                    $icon = 'fa-circle';
                                    $iconColor = 'text-blue-500';
                                    $bgColor = 'bg-blue-50';
                                    $link = null;
                                    $isClickable = false;
                                    
                                    if (str_contains($activity->action, 'login')) {
                                        $icon = 'fa-sign-in-alt';
                                        $iconColor = 'text-green-500';
                                        $bgColor = 'bg-green-50';
                                    } elseif (str_contains($activity->action, 'logout')) {
                                        $icon = 'fa-sign-out-alt';
                                        $iconColor = 'text-red-500';
                                        $bgColor = 'bg-red-50';
                                    } elseif (str_contains($activity->action, 'ad_created')) {
                                        $icon = 'fa-bullhorn';
                                        $iconColor = 'text-primary';
                                        $bgColor = 'bg-primary/10';
                                        $isClickable = true;
                                        // Get ad from model
                                        if ($activity->model_type && $activity->model_id) {
                                            $modelClass = $activity->model_type;
                                            if (class_exists($modelClass)) {
                                                $model = $modelClass::find($activity->model_id);
                                                if ($model && $model instanceof \App\Models\Ad) {
                                                    $link = route('ads.show', $model->uid);
                                                }
                                            }
                                        }
                                    } elseif (str_contains($activity->action, 'favorite')) {
                                        $icon = 'fa-heart';
                                        $iconColor = 'text-pink-500';
                                        $bgColor = 'bg-pink-50';
                                        $isClickable = true;
                                        // Get ad from model
                                        if ($activity->model_type && $activity->model_id) {
                                            $modelClass = $activity->model_type;
                                            if (class_exists($modelClass)) {
                                                $model = $modelClass::find($activity->model_id);
                                                if ($model && $model instanceof \App\Models\Ad) {
                                                    $link = route('ads.show', $model->uid);
                                                }
                                            }
                                        }
                                    } elseif (str_contains($activity->action, 'rating')) {
                                        $icon = 'fa-star';
                                        $iconColor = 'text-yellow-500';
                                        $bgColor = 'bg-yellow-50';
                                        $isClickable = true;
                                        // Get seller from metadata or model
                                        if ($activity->metadata && isset($activity->metadata['seller_id'])) {
                                            $seller = \App\Models\User::find($activity->metadata['seller_id']);
                                            if ($seller) {
                                                $link = route('seller.show', $seller->slug);
                                            }
                                        } elseif ($activity->model_type && $activity->model_id) {
                                            $modelClass = $activity->model_type;
                                            if (class_exists($modelClass)) {
                                                $model = $modelClass::find($activity->model_id);
                                                if ($model && $model instanceof \App\Models\SellerRating) {
                                                    $seller = $model->seller;
                                                    if ($seller) {
                                                        $link = route('seller.show', $seller->slug);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @if($isClickable && $link)
                                    <a href="{{ $link }}" class="block border border-gray-200 rounded-lg p-4 hover:bg-gray-50 hover:border-primary transition cursor-pointer">
                                @else
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                @endif
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full {{ $bgColor }} flex items-center justify-center">
                                                <i class="fas {{ $icon }} {{ $iconColor }}"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 mb-1">
                                                {{ $activity->description ?? __('frontend.profile.activity.' . $activity->action) }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-gray-500">
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $activity->created_at->format('Y-m-d H:i') }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-history"></i>
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            @if($isClickable && $link)
                                                <div class="mt-2 flex items-center gap-1 text-primary text-xs">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    <span>{{ __('frontend.profile.view_details') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @if($isClickable && $link)
                                    </a>
                                @else
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-history text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500">{{ __('frontend.profile.no_activities') }}</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

