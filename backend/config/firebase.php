<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Firebase settings for Cloud Messaging (FCM).
    | The service account JSON file should be placed in storage/app/firebase/
    |
    */

    'credentials' => env('FIREBASE_CREDENTIALS') ?: (
        file_exists(storage_path('app/firebase/service-account.json'))
            ? storage_path('app/firebase/service-account.json')
            : base_path('Firebase_key/Firebase_key.json')
    ),

    'project_id' => env('FIREBASE_PROJECT_ID', 'aalenha-91516'),

    /*
    |--------------------------------------------------------------------------
    | FCM Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for Firebase Cloud Messaging notifications
    |
    */

    'fcm' => [
        'default_sound' => 'default',
        'default_channel_id' => 'default',
        'priority' => 'high',
    ],

];
