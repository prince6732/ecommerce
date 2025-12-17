<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'shipping_fee',
        'tax',
        'total',
        'shipping_address',
        'billing_address',
        'notes',
        'shipped_at',
        'delivered_at',
        'delivery_confirmation_token',
        'delivery_confirmed_at',
        'delivery_confirmation_sent_at',
        'delhivery_waybill',
        'delhivery_status',
        'delhivery_status_updated_at',
        'delhivery_tracking_data',
        'courier_name',
        'delivery_instructions',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_confirmed_at' => 'datetime',
        'delivery_confirmation_sent_at' => 'datetime',
        'delhivery_status_updated_at' => 'datetime',
        'delhivery_tracking_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function trackingRecords(): HasMany
    {
        return $this->hasMany(OrderTracking::class);
    }

    public static function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function addTracking($status, $description, $location = null)
    {
        return $this->trackingRecords()->create([
            'status' => $status,
            'description' => $description,
            'location' => $location,
            'tracked_at' => now(),
        ]);
    }

    public function updateStatus($status, $description, $location = null)
    {
        $this->update(['status' => $status]);

        $this->addTracking($status, $description, $location);

        if ($status === 'shipped') {
            $this->update(['shipped_at' => now()]);
        } elseif ($status === 'delivered') {
            $this->update(['delivered_at' => now()]);
        }

        return $this;
    }

    public function getLatestTracking()
    {
        return $this->trackingRecords()->latest('tracked_at')->first();
    }

    public function generateDeliveryConfirmationToken()
    {
        $this->delivery_confirmation_token = bin2hex(random_bytes(32));
        $this->delivery_confirmation_sent_at = now();
        $this->save();

        return $this->delivery_confirmation_token;
    }

    public function canConfirmDelivery()
    {
        return $this->status === 'delivered'
            && $this->delivery_confirmation_token
            && !$this->delivery_confirmed_at;
    }

    public function confirmDelivery()
    {
        if (!$this->canConfirmDelivery()) {
            return false;
        }

        $this->delivery_confirmed_at = now();
        $this->status = 'completed';
        $this->delivery_confirmation_token = null;
        $this->save();

        $this->addTracking(
            'completed',
            'Order completed - Delivery confirmed by customer',
            'Customer Location'
        );

        return true;
    }
}
