<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validate Resource Server IP Address
    |--------------------------------------------------------------------------
    |
    | When enabled, validates that the resource server IP address matches
    | the request IP and the access token audience.
    |
    */
    'validate_resource_server_ip' => env('OAUTH2_VALIDATE_RESOURCE_SERVER_IP', true),
];
