<?php

return [
    // Whether or not Telemetry should be sent for storage.
    'enabled' => env('TELEMETRY_ENABLED', false),

    // By default, the index name found in the environment will be used to index
    // documents. If none found there, 'telemetry' will be used. However, this
    // value can possibly be overriden if the application binds a
    // TelemetryIndexResolver implementation to the service container.
    'index' => env('TELEMETRY_INDEX', 'telemetry'),

    // Unless specified in the fire() method, the default transport used to send
    // Telemetry payloads off to be processed.
    'default_transport' => env('TELEMETRY_DEFAULT_TRANSPORT', 'redis'),

    'connections' => [
        'redis' => [
            'driver'     => 'redis',
            'connection' => env('TELEMETRY_REDIS_CONNECTION'),
            'queue'      => env('TELEMETRY_REDIS_QUEUE'),
        ],
    ],

    'payloads' => [
        'request' => [
            // Global setting to include the request object in Telemetry payload.
            // This can be overridden using ->withRequestData().
            'included' => env('TELEMETRY_PAYLOADS_REQUEST_INCLUDED', true),

            // When Telemetry includes the request data in the payload, this list
            // of headers will attempt to be resolved from the request object.
            // Comma-separated header keys are first attempted from the environment
            // config.
            'headers' => empty(env('TELEMETRY_REQUEST_HEADERS')) === false ?
                explode(',', env('TELEMETRY_REQUEST_HEADERS')) :
                [
                    'accept',
                    'authorization',
                    'content-length',
                    'content-type',
                    'origin',
                ],
        ],

        'user' => [
            // Global setting to include the user object in Telemetry payload.
            // This can be overridden using ->withUserData().
            'included' => env('TELEMETRY_PAYLOADS_USER_INCLUDED', true),

            // When Telemetry includes a user in the payload, this list of
            // attributes will attempt to be resolved from the user object.
            // Comma-separated user attribute keys are first attempted from the
            // environment config.
            'attributes' => empty(env('TELEMETRY_USER_ATTRIBUTES')) === false ?
                explode(',', env('TELEMETRY_USER_ATTRIBUTES')) :
                [
                    'email',
                    'id',
                    'name',
                ],

            // If there is a callback method on the application's user object,
            // the array returned from that method will be included in the user
            // attributes for the Telemetry payload.
            'callback_method' => null,
        ],

        // Payload attributes that should be obfuscated in the data going into
        // the index. Obfuscation recursively walks the payload array, so any
        // value that is at the tail of an array will be obfuscated when matched.
        // Dot-notation is not currently possible.
        'obfuscated_data_keys' => empty(env('TELEMETRY_OBFUSCATED_DATA_KEYS')) === false ?
            explode(',', env('TELEMETRY_OBFUSCATED_DATA_KEYS')) :
            [
                'password',
                'password_confirmation',
            ],
    ],

    'notifications' => [
        // When logging errors/exceptions, which logging channel to use.
        'logging_channel' => env(
            'TELEMETRY_LOGGING_CHANNEL',
            config('logging.default')
        ),

        // By default, attempts to send Telemetry payloads out to be indexed will
        // NOT cause an exception to the application - the application shouldn't
        // fail because of logging issues. This configuration allows you to throw
        // any exception caught.
        'throw_transport_exceptions' => env(
            'TELEMETRY_THROW_TRANSPORT_EXCEPTIONS',
            false
        ),
    ],
];
