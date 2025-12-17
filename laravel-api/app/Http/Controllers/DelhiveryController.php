<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DelhiveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DelhiveryController extends Controller
{
    protected $delhiveryService;

    public function __construct(DelhiveryService $delhiveryService)
    {
        $this->delhiveryService = $delhiveryService;
    }

    public function createShipment(Request $request, $orderId)
    {
        try {
            $order = Order::with('user', 'orderItems')->findOrFail($orderId);

            $addressLines = array_filter(array_map('trim', explode("\n", $order->shipping_address)));
            $addressLines = array_values($addressLines);

            $customerName = $addressLines[0] ?? $order->user->name;
            $phone = preg_replace('/[^0-9]/', '', $addressLines[1] ?? $order->user->phone_number);

            $pincode = '';
            $city = '';
            $state = '';
            $addressParts = [];

            foreach ($addressLines as $index => $line) {
                if ($index <= 1) continue;

                if (preg_match('/(\d{6})/', $line, $pincodeMatch)) {
                    $pincode = $pincodeMatch[1];

                    if (preg_match('/([^,\d]+),\s*([^,\d]+)/', $line, $locationMatch)) {
                        $city = trim($locationMatch[1]);
                        $state = trim($locationMatch[2]);
                    }
                } else {
                    $addressParts[] = $line;
                }
            }

            $fullAddress = implode(', ', $addressParts);

            if (empty($pincode) || empty($city) || empty($state)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid shipping address format. Missing pincode, city, or state.',
                    'debug' => [
                        'pincode' => $pincode,
                        'city' => $city,
                        'state' => $state,
                        'raw_address' => $order->shipping_address
                    ]
                ], 400);
            }

            $orderData = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $customerName,
                'address' => $fullAddress,
                'city' => $city,
                'state' => $state,
                'postal_code' => $pincode,
                'country' => 'India',
                'phone' => $phone,
                'payment_method' => $order->payment_method,
                'total' => $order->total,
                'total_items' => $order->orderItems->sum('quantity'),
                'products_description' => $order->orderItems->pluck('product.name')->implode(', ') ?: 'General Items',
                'weight' => $request->input('weight', 0.5),
                'width' => $request->input('width', 10),
                'height' => $request->input('height', 10),
            ];

            $result = $this->delhiveryService->createShipment($orderData);

            if ($result['success'] && !empty($result['waybill'])) {
                $order->update([
                    'delhivery_waybill' => $result['waybill'],
                    'delhivery_status' => 'Shipped',
                    'delhivery_status_updated_at' => now(),
                    'status' => 'shipped',
                    'courier_name' => 'Delhivery'
                ]);

                $order->addTracking('shipped', 'Shipment created with Delhivery', 'Delhivery');

                return response()->json([
                    'success' => true,
                    'message' => 'Shipment created successfully',
                    'waybill' => $result['waybill'],
                    'order' => $order->load('trackingRecords')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Delhivery account verification required. Please contact Delhivery support.',
                'error' => $result['error'] ?? 'Your Delhivery account needs to be verified before creating shipments. Contact: nilanshu.singh@delhivery.com',
                'details' => 'Error Code: ER0005 - Suspicious order/consignee. Your pickup location is active but API access needs approval.'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating Delhivery shipment', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackByOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            if (!$order->delhivery_waybill) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tracking information available for this order'
                ], 404);
            }

            $result = $this->delhiveryService->trackShipment($order->delhivery_waybill);

            if ($result['success']) {
                $order->update([
                    'delhivery_status' => $result['tracking_data']['status'] ?? null,
                    'delhivery_status_updated_at' => now(),
                    'delhivery_tracking_data' => json_encode($result['tracking_data'])
                ]);

                return response()->json([
                    'success' => true,
                    'order_number' => $order->order_number,
                    'waybill' => $order->delhivery_waybill,
                    'tracking_data' => $result['tracking_data']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error tracking Delhivery shipment', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackByWaybill(Request $request)
    {
        $request->validate([
            'waybill' => 'required|string'
        ]);

        try {
            $result = $this->delhiveryService->trackShipment($request->waybill);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'waybill' => $request->waybill,
                    'tracking_data' => $result['tracking_data']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkServiceability(Request $request)
    {
        $request->validate([
            'pincode' => 'required|string|size:6'
        ]);

        try {
            $result = $this->delhiveryService->checkServiceability($request->pincode);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check serviceability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelShipment($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            if (!$order->delhivery_waybill) {
                return response()->json([
                    'success' => false,
                    'message' => 'No shipment found for this order'
                ], 404);
            }

            $result = $this->delhiveryService->cancelShipment($order->delhivery_waybill);

            if ($result['success']) {
                $order->update([
                    'delhivery_status' => 'Cancelled',
                    'delhivery_status_updated_at' => now(),
                    'status' => 'cancelled'
                ]);

                $order->addTracking('cancelled', 'Shipment cancelled', 'Delhivery');

                return response()->json([
                    'success' => true,
                    'message' => 'Shipment cancelled successfully',
                    'order' => $order->load('trackingRecords')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function syncTracking($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            if (!$order->delhivery_waybill) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tracking information available'
                ], 404);
            }

            $result = $this->delhiveryService->trackShipment($order->delhivery_waybill);

            if ($result['success']) {
                $trackingData = $result['tracking_data'];

                $order->update([
                    'delhivery_status' => $trackingData['status'],
                    'delhivery_status_updated_at' => now(),
                    'delhivery_tracking_data' => json_encode($trackingData)
                ]);

                $this->updateOrderStatus($order, $trackingData['status']);

                return response()->json([
                    'success' => true,
                    'message' => 'Tracking synced successfully',
                    'tracking_data' => $trackingData
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync tracking'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync tracking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function updateOrderStatus(Order $order, string $delhiveryStatus)
    {
        $statusMap = [
            'Dispatched' => 'shipped',
            'In Transit' => 'shipped',
            'Out for Delivery' => 'shipped',
            'Delivered' => 'delivered',
            'RTO' => 'cancelled',
            'Cancelled' => 'cancelled'
        ];

        $newStatus = $statusMap[$delhiveryStatus] ?? $order->status;

        if ($newStatus !== $order->status) {
            $order->update(['status' => $newStatus]);
            $order->addTracking($newStatus, "Status updated: {$delhiveryStatus}", 'Delhivery');
        }
    }

    public function getWarehouses()
    {
        try {
            $result = $this->delhiveryService->getWarehouses();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch warehouses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
