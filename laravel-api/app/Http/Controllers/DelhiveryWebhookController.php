<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DelhiveryWebhookController extends Controller
{

    public function handleWebhook(Request $request)
    {
        Log::info('Delhivery webhook received', $request->all());

        try {
            $waybill = $request->input('waybill');
            $status = $request->input('status');
            $statusCode = $request->input('status_code');

            if (!$waybill) {
                return response()->json(['error' => 'Waybill is required'], 400);
            }

            $order = Order::where('delhivery_waybill', $waybill)->first();

            if (!$order) {
                Log::warning('Order not found for waybill', ['waybill' => $waybill]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            $orderStatus = $this->mapDelhiveryStatus($status ?? $statusCode);

            $order->delhivery_status = $status ?? $statusCode;
            $order->delhivery_status_updated_at = now();
            $order->delhivery_tracking_data = json_encode($request->all());

            if ($orderStatus && $order->status !== $orderStatus) {
                $order->status = $orderStatus;

                if ($orderStatus === 'delivered' && !$order->delivered_at) {
                    $order->delivered_at = now();
                }
            }

            $order->save();

            $order->trackingRecords()->create([
                'status' => $order->status,
                'description' => "Delhivery webhook: {$status}",
                'location' => $request->input('location', 'Delhivery'),
                'tracked_at' => now(),
            ]);

            Log::info('Order updated via webhook', [
                'order_id' => $order->id,
                'waybill' => $waybill,
                'status' => $status
            ]);

            return response()->json(['success' => true, 'message' => 'Order updated']);
        } catch (\Exception $e) {
            Log::error('Delhivery webhook error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function mapDelhiveryStatus(string $delhiveryStatus): ?string
    {
        $statusMap = [
            'Pending' => 'processing',
            'Dispatched' => 'shipped',
            'In Transit' => 'shipped',
            'in-transit' => 'shipped',
            'Out for Delivery' => 'shipped',
            'out-for-delivery' => 'shipped',
            'Delivered' => 'delivered',
            'delivered' => 'delivered',
            'RTO' => 'cancelled',
            'Cancelled' => 'cancelled',
            'cancelled' => 'cancelled',
            'Lost' => 'cancelled',
        ];

        return $statusMap[$delhiveryStatus] ?? null;
    }
}
