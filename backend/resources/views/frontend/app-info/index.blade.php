@extends('frontend.layouts.app')

@section('title', __('frontend.app_info.title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white py-8 sm:py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('frontend.app_info.title') }}</h1>
                <p class="text-blue-100 text-lg">{{ __('frontend.app_info.subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Map Section -->
            @if($appInfo['map_location_url'])
            <div class="bg-white rounded-xl shadow-lg mb-6 overflow-hidden">
                <div id="map" class="w-full h-64 sm:h-96"></div>
                <div class="p-4 bg-gray-50 border-t">
                    <a href="{{ $appInfo['map_location_url'] }}" target="_blank" class="text-primary hover:underline flex items-center gap-2">
                        <i class="fas fa-external-link-alt"></i>
                        {{ __('frontend.app_info.view_on_google_maps') }}
                    </a>
                </div>
            </div>
            @endif

            <!-- Information Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Establishment Name -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-lg">
                            <i class="fas fa-building text-primary text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.establishment_name') }}</h3>
                            <p class="text-lg font-bold text-gray-800">{{ $appInfo['establishment_name'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Commercial Name -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-secondary bg-opacity-10 p-3 rounded-lg">
                            <i class="fas fa-store text-secondary text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.commercial_name') }}</h3>
                            <p class="text-lg font-bold text-gray-800">{{ $appInfo['commercial_name'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Responsible Person -->
                @if($appInfo['responsible_person'])
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-user-tie text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.responsible_person') }}</h3>
                            <p class="text-lg font-bold text-gray-800">{{ $appInfo['responsible_person'] }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Commercial Registration Number -->
                @if($appInfo['commercial_registration_number'])
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-start gap-4">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-file-contract text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.commercial_registration_number') }}</h3>
                            <p class="text-lg font-bold text-gray-800">{{ $appInfo['commercial_registration_number'] }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Detailed Information -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-primary to-blue-900 text-white p-6">
                    <h2 class="text-2xl font-bold flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        {{ __('frontend.app_info.detailed_info') }}
                    </h2>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Official Email -->
                    <div class="flex items-start gap-4 pb-4 border-b border-gray-200">
                        <div class="bg-red-100 p-2 rounded-lg">
                            <i class="fas fa-envelope text-red-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.official_email') }}</h3>
                            <a href="mailto:{{ $appInfo['official_email'] }}" class="text-primary hover:underline font-semibold">
                                {{ $appInfo['official_email'] }}
                            </a>
                        </div>
                    </div>

                    <!-- MERSIS Number -->
                    <div class="flex items-start gap-4 pb-4 border-b border-gray-200">
                        <div class="bg-purple-100 p-2 rounded-lg">
                            <i class="fas fa-hashtag text-purple-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.mersis_number') }}</h3>
                            <p class="text-gray-800 font-semibold">{{ $appInfo['mersis_number'] }}</p>
                        </div>
                    </div>

                    <!-- Main Office -->
                    @if($appInfo['main_office'])
                    <div class="flex items-start gap-4 pb-4 border-b border-gray-200">
                        <div class="bg-yellow-100 p-2 rounded-lg">
                            <i class="fas fa-map-marker-alt text-yellow-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.main_office') }}</h3>
                            <p class="text-gray-800 font-semibold">{{ $appInfo['main_office'] }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Call Center -->
                    @if($appInfo['call_center'])
                    <div class="flex items-start gap-4 pb-4 border-b border-gray-200">
                        <div class="bg-green-100 p-2 rounded-lg">
                            <i class="fas fa-phone text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.call_center') }}</h3>
                            <a href="tel:{{ $appInfo['call_center'] }}" class="text-primary hover:underline font-semibold">
                                {{ $appInfo['call_center'] }}
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Support Center -->
                    <div class="flex items-start gap-4">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <i class="fas fa-headset text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">{{ __('frontend.app_info.support_center') }}</h3>
                            <p class="text-gray-800 font-semibold">{{ $appInfo['support_center'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($appInfo['map_location_url'])
@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key', 'YOUR_API_KEY') }}&libraries=places&language={{ app()->getLocale() }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lat = parseFloat({{ $appInfo['map_latitude'] }}) || 33.5138;
    const lng = parseFloat({{ $appInfo['map_longitude'] }}) || 36.2765;

    const map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: lat, lng: lng },
        zoom: 15,
        mapTypeControl: true,
        streetViewControl: true,
    });

    const marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        title: '{{ $appInfo['establishment_name'] }}',
        animation: google.maps.Animation.DROP,
    });

    const infoWindow = new google.maps.InfoWindow({
        content: `<div class="p-2"><strong>{{ $appInfo['establishment_name'] }}</strong><br><a href="{{ $appInfo['map_location_url'] }}" target="_blank">View on Google Maps</a></div>`,
    });

    marker.addListener('click', function() {
        infoWindow.open(map, marker);
    });
});
</script>
@endpush
@endif
@endsection

