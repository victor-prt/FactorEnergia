<?php

return [
    'adminEmail' => 'admin@example.com',
    'erse' => [
        'baseUrl' => rtrim(getenv('ERSE_BASE_URL') ?: 'https://api.erse.pt/v2', '/'),
        'bearerToken' => getenv('ERSE_BEARER_TOKEN') ?: '',
        'tariffCodeDefault' => getenv('ERSE_TARIFF_CODE') ?: 'ERSE_REGULATED_01',
        'defaultEstimatedAnnualKwh' => (int) (getenv('ERSE_DEFAULT_ANNUAL_KWH') ?: 3500),
        'defaultAddress' => [
            'street' => getenv('ERSE_DEFAULT_STREET') ?: 'Rua de exemplo 1',
            'city' => getenv('ERSE_DEFAULT_CITY') ?: 'Lisboa',
            'postal_code' => getenv('ERSE_DEFAULT_POSTAL') ?: '1000-001',
        ],
        'mock' => in_array(
            strtolower((string) (getenv('ERSE_MOCK') ?: '')),
            ['1', 'true', 'yes'],
            true
        ),
    ],
];
