<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\DelhiveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDelhiveryTracking extends Command
{
    protected $signature = 'delhivery:sync-tracking {--order_id=}';
    protected $description = 'Sync tracking status from Delhivery for all orders with waybill';

    protected $delhiveryService;

    public function __construct(DelhiveryService $delhiveryService)
    {
        parent::__construct();
        $this->delhiveryService = $delhiveryService;
    }

    public function handle()
    {
        $this->info('Starting Delhivery tracking sync...');

        // Get orders with Delhivery waybill that are not delivered/cancelled
        $query = Order::whereNotNull('delhivery_waybill')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->where('courier_name', 'Delhivery');

        // If specific order ID is provided
        if ($orderId = $this->option('order_id')) {
            $query->where('id', $orderId);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info('No orders to sync.');
            return 0;
        }

        $this->info("Found {$orders->count()} orders to sync.");

        $successCount = 0;
        $errorCount = 0;

        foreach ($orders as $order) {
            $this->line("Syncing Order #{$order->order_number} (Waybill: {$order->delhivery_waybill})...");

            try {
                $result = $this->delhiveryService->trackShipment($order->delhivery_waybill);

                if ($result['success'] && isset($result['raw_data']['ShipmentData'])) {
                    $trackingData = $result['raw_data'];
                    $shipmentData = $trackingData['ShipmentData'][0] ?? null;

                    if ($shipmentData && isset($shipmentData['Shipment'])) {
                        $shipment = $shipmentData['Shipment'];
                        $status = $shipment['Status'];
                        $statusCode = $status['Status'] ?? '';

                        // Update order status based on Delhivery status
                        $this->updateOrderStatus($order, $statusCode, $trackingData);

                        $this->info("✓ Updated Order #{$order->order_number}: {$statusCode}");
                        $successCount++;
                    } else {
                        $this->warn("✗ No shipment data for Order #{$order->order_number}");
                        $errorCount++;
                    }
                } else {
                    $this->warn("✗ Failed to fetch tracking for Order #{$order->order_number}");
                    $errorCount++;
                }

                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds

            } catch (\Exception $e) {
                $this->error("✗ Error syncing Order #{$order->order_number}: " . $e->getMessage());
                Log::error('Delhivery sync error', [
                    'order_id' => $order->id,
                    'waybill' => $order->delhivery_waybill,
                    'error' => $e->getMessage()
                ]);
                $errorCount++;
            }
        }

        $this->info("\n=== Sync Complete ===");
        $this->info("Success: {$successCount}");
        $this->info("Errors: {$errorCount}");

        return 0;
    }

    protected function updateOrderStatus(Order $order, string $delhiveryStatus, array $trackingData)
    {
        $statusMap = [
            'Pending' => 'processing',
            'Manifested' => 'shipped',
            'Dispatched' => 'shipped',
            'In Transit' => 'shipped',
            'Out for Delivery' => 'shipped',
            'Delivered' => 'delivered',
            'RTO' => 'cancelled',
            'Cancelled' => 'cancelled',
            'Lost' => 'cancelled',
        ];

        $newStatus = $statusMap[$delhiveryStatus] ?? null;

        // Only update Delhivery tracking data, don't change order status if already shipped
        if ($order->status === 'shipped' && $newStatus === 'processing') {
            // Don't downgrade from shipped to processing
            $newStatus = 'shipped';
        }

        // Only update if status has changed
        if ($newStatus && ($order->status !== $newStatus || $order->delhivery_status !== $delhiveryStatus)) {
            $order->status = $newStatus;
            $order->delhivery_status = $delhiveryStatus;
            $order->delhivery_status_updated_at = now();
            $order->delhivery_tracking_data = json_encode($trackingData);

            // Set delivered_at timestamp if delivered
            if ($newStatus === 'delivered' && !$order->delivered_at) {
                $order->delivered_at = now();
            }

            $order->save();

            // Add tracking record
            $order->trackingRecords()->create([
                'status' => $newStatus,
                'description' => "Delhivery update: {$delhiveryStatus}",
                'location' => $this->extractLocation($trackingData),
                'tracked_at' => now(),
            ]);
        }
    }

    protected function extractLocation(array $trackingData): string
    {
        if (isset($trackingData['ShipmentData'][0]['Shipment']['Scans'][0])) {
            $lastScan = $trackingData['ShipmentData'][0]['Shipment']['Scans'][0];
            return $lastScan['ScanDetail']['ScannedLocation'] ?? 'Delhivery';
        }
        return 'Delhivery';
    }
}
