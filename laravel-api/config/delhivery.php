<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Delhivery API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Delhivery courier and logistics API integration
    |
    */

    'api_key' => env('DELHIVERY_API_KEY'),
    
    'api_url' => env('DELHIVERY_API_URL', 'https://track.delhivery.com/api'),
    
    'staging_url' => env('DELHIVERY_STAGING_URL', 'https://staging-express.delhivery.com/api'),
    
    'environment' => env('DELHIVERY_ENVIRONMENT', 'production'),
    
    'client_name' => env('DELHIVERY_CLIENT_NAME'),
    
    'base_url' => env('DELHIVERY_ENVIRONMENT', 'production') === 'production' 
        ? env('DELHIVERY_API_URL', 'https://track.delhivery.com/api')
        : env('DELHIVERY_STAGING_URL', 'https://staging-express.delhivery.com/api'),
];
