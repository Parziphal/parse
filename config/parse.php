<?php

return [

    /**
     *
     */
    'app_id' => env('PARSE_APP_ID', 'app_id'),

    /**
     *
     */
    'rest_key' => env('PARSE_REST_KEY', 'rest_key'),

    /**
     *
     */
    'master_key' => env('PARSE_MASTER_KEY', 'master_key'),

    /**
     *
     */
    'server_url' => env('PARSE_SERVER_URL', 'server_url'),

    /**
     * Default user class. It must extend Parziphal\Parse\UserModel.
     */
    'user_class' => Parziphal\Parse\UserModel::class,

];
