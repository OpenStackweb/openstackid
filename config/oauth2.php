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
    'validate_resource_server_ip' => env('OAUTH2_VALIDATE_RESOURCE_SERVER_IP', false),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Console Allowed Groups
    |--------------------------------------------------------------------------
    |
    | Comma separated list of group slugs whose members can see the
    | "OAUTH2 Console" menu and use /admin/clients, /admin/grants and the
    | related admin API. If empty or unset, NOBODY has access (secure default).
    |
    */
    'console_allowed_groups' => array_values(array_filter(array_map('trim', explode(',', (string)env('OAUTH2_CONSOLE_ALLOWED_GROUPS', ''))))),
];
