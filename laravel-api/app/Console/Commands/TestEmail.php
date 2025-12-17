<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Mail\DeliveryConfirmationMail;

class TestEmail extends Command
{
    protected $signature = 'test:email {orderId?}';
    protected $description = 'Test delivery confirmation email';

    public function handle()
    {
        $orderId = $this->argument('orderId');
        
        if (!$orderId) {
            $this->error('Please provide an order ID');
            $this->info('Usage: php artisan test:email {orderId}');
            return 1;
        }

        $order = Order::with(['user', 'orderItems.product', 'orderItems.variant'])->find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return 1;
        }

        $this->info("Testing email for Order #{$order->order_number}");
        $this->info("Customer Email: {$order->user->email}");
        
        try {
            // Generate token if not exists
            if (!$order->delivery_confirmation_token) {
                $token = $order->generateDeliveryConfirmationToken();
            } else {
                $token = $order->delivery_confirmation_token;
            }
            
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $confirmationUrl = $frontendUrl . '/confirm-delivery/' . $token;
            
            $this->info("Confirmation URL: {$confirmationUrl}");
            $this->info("Sending email...");
            
            Mail::to($order->user->email)->send(new DeliveryConfirmationMail($order, $confirmationUrl));
            
            $this->info('✓ Email sent successfully!');
            $this->info('Check your email inbox at: ' . $order->user->email);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email');
            $this->error('Error: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
