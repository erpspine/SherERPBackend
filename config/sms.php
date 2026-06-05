<?php

return [
    'enabled' => env('SMS_ENABLED', false),
    'driver' => env('SMS_DRIVER', 'messaging_service'),
    'endpoint' => env('SMS_ENDPOINT', 'https://messaging-service.co.tz/api/sms/v1/text/single'),
    'test_endpoint' => env(
        'SMS_TEST_ENDPOINT',
        'https://messaging-service.co.tz/api/sms/v1/test/text/single'
    ),
    'test_mode' => env('SMS_TEST_MODE', false),
    'username' => env('SMS_USERNAME'),
    'password' => env('SMS_PASSWORD'),
    'auth_header' => env('SMS_AUTH_HEADER'),
    'from' => env('SMS_FROM', 'SHER EA LTD'),
    'timeout' => (int) env('SMS_TIMEOUT', 30),
    'log_requests' => env('SMS_LOG_REQUESTS', false),
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '255'),
];
