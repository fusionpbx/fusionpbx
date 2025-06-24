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

    'CHECK_CALLCENTERS' => 1,
    'CHECK_CONFERENCES' => 2,
    'CHECK_CONFERENCECENTERS' => 4,
    'CHECK_EXTENSIONS' => 8,
    'CHECK_FAXES' => 16,
    'CHECK_IVRS' => 32,
    'CHECK_RINGGROUPS' => 64,

    'ALLOW_CONTEXT_SESSION_MISMATCH' => 1,
    'ALLOW_PUBLIC_CONTEXT' => 2,
    'ALLOW_GLOBAL_CONTEXT' => 4,
];
