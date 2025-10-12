<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */
    'cloud_url' => env('CLOUDINARY_URL'),

    // Optional preset from your Cloudinary dashboard
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', 'my_tor_uploads'),

    // Optional notification webhook
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

    // Optional extra config
    'upload_route' => env('CLOUDINARY_UPLOAD_ROUTE'),
    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION'),
];
