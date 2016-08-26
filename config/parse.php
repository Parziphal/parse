<?php

return [

    /**
     * Parse App Id
     */
    'app_id' => env('PARSE_APP_ID'),

    /**
     * Parse REST key
     */
    'rest_key' => env('PARSE_REST_KEY'),

    /**
     * Parse Master Key
     */
    'master_key' => env('PARSE_MASTER_KEY'),

    /**
     * Parse Server URL
     */
    'server_url' => env('PARSE_SERVER_URL', 'http://127.0.0.1:1337'),

    /**
     * Parse Server Mount Path
     */
    'mount_path' => env('PARSE_MOUNT_PATH', '/parse'),

];
