<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Http;

class UpsRateService
{
    public function quote(array $data): array
    {
        $accessKey = env('UPS_ACCESS_KEY');
        $userId = env('UPS_USER_ID');
        $password = env('UPS_PASSWORD');

        if (!$accessKey || !$userId || !$password) {
            return ['success' => false, 'message' => 'UPS credentials are missing.', 'shipping_plans' => []];
        }

        $endpoint = env('UPS_SANDBOX') ? 'https://wwwcie.ups.com/rest/Rate' : 'https://onlinetools.ups.com/rest/Rate';

        $payload = [
            'UPSSecurity' => [
                'UsernameToken' => [
                    'Username' => $userId,
                    'Password' => $password,
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $accessKey,
                ],
            ],
            'RateRequest' => [
                'Request' => [
                    'RequestOption' => 'Rate',
                ],
                'Shipment' => [
                    'Shipper' => [
                        'Address' => [
                            'AddressLine' => [$data['from_address'] ?? ''],
                            'City' => $data['from_city'] ?? '',
                            'StateProvinceCode' => $data['from_state_province_code'] ?? '',
                            'PostalCode' => $data['from_zipcode'] ?? '',
                            'CountryCode' => $data['from_countries'] ?? '',
                        ],
                    ],
                    'ShipTo' => [
                        'Address' => [
                            'AddressLine' => [$data['destination_address'] ?? ''],
                            'City' => $data['destination_city'] ?? '',
                            'StateProvinceCode' => $data['destination_state_province_code'] ?? '',
                            'PostalCode' => $data['destination_zipcode'] ?? '',
                            'CountryCode' => $data['destination_countries'] ?? '',
                        ],
                    ],
                    'ShipFrom' => [
                        'Address' => [
                            'AddressLine' => [$data['from_address'] ?? ''],
                            'City' => $data['from_city'] ?? '',
                            'StateProvinceCode' => $data['from_state_province_code'] ?? '',
                            'PostalCode' => $data['from_zipcode'] ?? '',
                            'CountryCode' => $data['from_countries'] ?? '',
                        ],
                    ],
                    'Service' => [
                        'Code' => '02',
                    ],
                    'Package' => [
                        'PackagingType' => [
                            'Code' => '02',
                        ],
                        'Dimensions' => [
                            'UnitOfMeasurement' => [
                                'Code' => 'IN',
                            ],
                            'Length' => (string) ($data['length'] ?? 0),
                            'Width' => (string) ($data['width'] ?? 0),
                            'Height' => (string) ($data['height'] ?? 0),
                        ],
                        'PackageWeight' => [
                            'UnitOfMeasurement' => [
                                'Code' => 'Lbs',
                            ],
                            'Weight' => (string) ($data['weight'] ?? 0),
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, $payload);

        if (!$response->ok()) {
            return ['success' => false, 'message' => 'UPS service unavailable.', 'shipping_plans' => []];
        }

        $json = $response->json();
        if (isset($json['Fault'])) {
            $msg = $json['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description'] ?? 'UPS error.';
            return ['success' => false, 'message' => $msg, 'shipping_plans' => []];
        }

        $total = $json['RateResponse']['RatedShipment']['RatedPackage']['TotalCharges'] ?? null;
        if (!$total) {
            return ['success' => false, 'message' => 'Unable to calculate UPS rate.', 'shipping_plans' => []];
        }

        return [
            'success' => true,
            'message' => 'Success.',
            'shipping_plans' => [[
                'service' => 'UPS',
                'cost' => ($total['MonetaryValue'] ?? '0') . ' ' . ($total['CurrencyCode'] ?? 'USD'),
            ]],
        ];
    }
}

