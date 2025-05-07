<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'api_type' => env('FS_API_TYPE', 'XML_RPC'),

    /*
    |--------------------------------------------------------------------------
    | FreeSwitch Connection Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how the application connects to the FreeSwitch
    | server via Event Socket Layer (ESL).
    |
    */
    
    'host' => env('FREESWITCH_HOST', '127.0.0.1'),
    'port' => env('FREESWITCH_PORT', 8021),
    'password' => env('FREESWITCH_PASSWORD', 'ClueCon'),
    'timeout' => env('FREESWITCH_TIMEOUT', 5),
    
    
    /*
    |--------------------------------------------------------------------------
    | Mock/Stub Mode
    |--------------------------------------------------------------------------
    |
    | When this is set to true, the system will use predefined mock responses
    | instead of making actual connections to FreeSwitch. This is useful for
    | development and testing environments where FreeSwitch might not be
    | available.
    |
    */
    
    'use_mocks' => env('FREESWITCH_USE_MOCKS', false),
    
    /*
    |--------------------------------------------------------------------------
    | Mock Response Files Directory
    |--------------------------------------------------------------------------
    |
    | This is the directory where mock XML response files are stored.
    | The path is relative to the storage directory.
    |
    */
    
    'mock_responses_dir' => env('FREESWITCH_MOCK_DIR', 'app/mock_responses/freeswitch'),

];
