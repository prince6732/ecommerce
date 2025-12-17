<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DelhiveryService
{
    protected $apiKey;
    protected $baseUrl;
    protected $clientName;

    public function __construct()
    {
        $this->apiKey = config('delhivery.api_key');
        $this->baseUrl = config('delhivery.base_url');
        $this->clientName = config('delhivery.client_name');
    }

    public function createShipment(array $orderData)
    {
        try {
            $shipmentData = $this->formatShipmentData($orderData);

            Log::info('Sending to Delhivery', [
                'url' => $this->baseUrl . '/cmu/create.json',
                'data' => $shipmentData
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Token ' . $this->apiKey,
            ])->asForm()->post($this->baseUrl . '/cmu/create.json', [
                'format' => 'json',
                'data' => json_encode($shipmentData)
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Delhivery API Response', [
                    'order_id' => $orderData['order_id'],
                    'status_code' => $response->status(),
                    'full_response' => $data
                ]);

                $waybill = null;
                $errorMessage = null;

                if (isset($data['packages']) && is_array($data['packages']) && count($data['packages']) > 0) {
                    $package = $data['packages'][0];
                    $waybill = $package['waybill'] ?? null;

                    if (isset($package['status']) && $package['status'] === 'Fail') {
                        $errorMessage = isset($package['remarks']) && is_array($package['remarks'])
                            ? implode(', ', $package['remarks'])
                            : 'Shipment creation failed';

                        Log::error('Delhivery shipment failed', [
                            'error_code' => $package['err_code'] ?? 'Unknown',
                            'remarks' => $package['remarks'] ?? [],
                            'waybill' => $waybill
                        ]);
                    }
                }

                if (empty($waybill)) {
                    return [
                        'success' => false,
                        'waybill' => null,
                        'message' => $errorMessage ?? 'Delhivery account verification required',
                        'error' => $errorMessage ?? 'Your Delhivery account needs API access approval. Contact: nilanshu.singh@delhivery.com',
                        'data' => $data
                    ];
                }

                return [
                    'success' => true,
                    'waybill' => $waybill,
                    'message' => 'Shipment created successfully',
                    'data' => $data
                ];
            }

            Log::error('Delhivery shipment creation failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_data' => $shipmentData
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create shipment',
                'error' => $response->body()
            ];
        } catch (Exception $e) {
            Log::error('Delhivery shipment creation error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error creating shipment',
                'error' => $e->getMessage()
            ];
        }
    }

    public function trackShipment(string $waybill)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
            ])->get($this->baseUrl . '/v1/packages/json/', [
                'waybill' => $waybill,
                'verbose' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'tracking_data' => $this->parseTrackingData($data),
                    'raw_data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to track shipment',
                'error' => $response->body()
            ];
        } catch (Exception $e) {
            Log::error('Delhivery tracking error', [
                'waybill' => $waybill,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error tracking shipment',
                'error' => $e->getMessage()
            ];
        }
    }

    public function checkServiceability(string $pincode)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
            ])->get($this->baseUrl . '/c/api/pin-codes/json/', [
                'filter_codes' => $pincode
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'serviceable' => !empty($data['delivery_codes']),
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'serviceable' => false,
                'message' => 'Failed to check serviceability'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'serviceable' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function cancelShipment(string $waybill)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
            ])->post($this->baseUrl . '/cmu/cancel.json', [
                'waybill' => $waybill
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Shipment cancelled successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to cancel shipment',
                'error' => $response->body()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error cancelling shipment',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function formatShipmentData(array $orderData)
    {
        $pickupLocation = env('DELHIVERY_PICKUP_LOCATION');
        $returnAddress = env('DELHIVERY_RETURN_ADDRESS');
        $returnCity = env('DELHIVERY_RETURN_CITY');
        $returnState = env('DELHIVERY_RETURN_STATE');
        $returnPin = env('DELHIVERY_RETURN_PIN');
        $returnPhone = env('DELHIVERY_RETURN_PHONE');

        return [
            'pickup_location' => [
                'name' => $pickupLocation,
            ],
            'shipments' => [[
                'name' => $orderData['customer_name'],
                'add' => $orderData['address'],
                'pin' => $orderData['postal_code'],
                'city' => $orderData['city'],
                'state' => $orderData['state'],
                'country' => $orderData['country'] ?? 'India',
                'phone' => $orderData['phone'],
                'order' => $orderData['order_number'],
                'payment_mode' => $orderData['payment_method'] === 'cash_on_delivery' ? 'COD' : 'Prepaid',
                'return_pin' => $returnPin,
                'return_city' => $returnCity,
                'return_phone' => $returnPhone,
                'return_add' => $returnAddress,
                'return_state' => $returnState,
                'return_country' => 'India',
                'products_desc' => $orderData['products_description'] ?? 'General Items',
                'hsn_code' => '',
                'cod_amount' => $orderData['payment_method'] === 'cash_on_delivery' ? (string)$orderData['total'] : '0',
                'order_date' => $orderData['order_date'] ?? now()->format('Y-m-d H:i:s'),
                'total_amount' => (string)$orderData['total'],
                'seller_add' => $returnAddress,
                'seller_name' => $this->clientName,
                'seller_inv' => $orderData['order_number'],
                'quantity' => $orderData['total_items'] ?? 1,
                'waybill' => '',
                'shipment_width' => $orderData['width'] ?? 10,
                'shipment_height' => $orderData['height'] ?? 10,
                'weight' => $orderData['weight'] ?? 0.5,
                'seller_gst_tin' => '',
                'shipping_mode' => 'Surface',
                'address_type' => 'home'
            ]]
        ];
    }

    protected function parseTrackingData(array $data)
    {
        if (empty($data['ShipmentData'])) {
            return [];
        }

        $shipmentData = $data['ShipmentData'][0] ?? [];
        $shipment = $shipmentData['Shipment'] ?? [];
        $scans = $shipment['Scans'] ?? [];

        return [
            'waybill' => $shipment['AWB'] ?? '',
            'status' => $shipment['Status']['Status'] ?? 'Unknown',
            'status_code' => $shipment['Status']['StatusCode'] ?? '',
            'status_date' => $shipment['Status']['StatusDateTime'] ?? '',
            'expected_delivery' => $shipment['PromisedDeliveryDate'] ?? '',
            'current_location' => $shipment['Status']['StatusLocation'] ?? '',
            'scans' => collect($scans)->map(function ($scan) {
                $scanDetail = $scan['ScanDetail'] ?? [];
                return [
                    'scan_date' => $scanDetail['ScanDateTime'] ?? '',
                    'scan_type' => $scanDetail['ScanType'] ?? '',
                    'scan_detail' => $scanDetail['Scan'] ?? '',
                    'location' => $scanDetail['ScannedLocation'] ?? '',
                    'instructions' => $scanDetail['Instructions'] ?? ''
                ];
            })->toArray()
        ];
    }

    public function getWarehouses()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
            ])->get($this->baseUrl . '/backend/clientwarehouse/all/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'warehouses' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch warehouses'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
