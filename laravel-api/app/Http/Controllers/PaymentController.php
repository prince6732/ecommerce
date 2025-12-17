<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $appId;
    private $secretKey;
    private $apiVersion;
    private $baseUrl;

    public function __construct()
    {
        $this->appId = env('CASHFREE_APP_ID');
        $this->secretKey = env('CASHFREE_SECRET_KEY');
        $this->apiVersion = env('CASHFREE_API_VERSION', '2023-08-01');
        $this->baseUrl = env('CASHFREE_BASE_URL', 'https://api.cashfree.com/pg');
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string',
            'billing_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'cart_items' => 'sometimes|array',
            'cart_items.*' => 'integer|exists:carts,id',
            'product_id' => 'sometimes|integer|exists:products,id',
            'variant_id' => 'sometimes|integer|exists:variants,id',
            'quantity' => 'sometimes|integer|min:1',
            'selected_attributes' => 'nullable|array',
        ]);

        try {
            $userId = Auth::id();
            $user = Auth::user();
            $cartItems = collect();
            $isSingleItem = false;

            if ($request->has('product_id') && $request->has('variant_id')) {
                $isSingleItem = true;
                $product = Product::findOrFail($request->product_id);
                $variant = Variant::findOrFail($request->variant_id);

                if ($variant->product_id !== $product->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid variant for the selected product',
                    ], 400);
                }

                if ($variant->stock < $request->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Sorry! Only {$variant->stock} items are currently available for this product.",
                    ], 400);
                }

                $itemTotal = $variant->sp * $request->quantity;

                $cartItems->push((object)[
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => $request->quantity,
                    'price' => $variant->sp,
                    'total' => $itemTotal,
                    'selected_attributes' => $request->selected_attributes,
                    'product' => $product,
                    'variant' => $variant,
                ]);
            } else {
                $cartQuery = Cart::with(['product', 'variant'])
                    ->where('user_id', $userId);

                if ($request->has('cart_items') && !empty($request->cart_items)) {
                    $cartQuery->whereIn('id', $request->cart_items);
                }

                $cartItems = $cartQuery->get();

                if ($cartItems->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your cart is empty. Please add items to your cart before placing an order.',
                    ], 400);
                }

                foreach ($cartItems as $item) {
                    if (!$item->variant) {
                        return response()->json([
                            'success' => false,
                            'message' => "Product variant not found. Please refresh your cart.",
                        ], 400);
                    }

                    if ($item->variant->stock < $item->quantity) {
                        return response()->json([
                            'success' => false,
                            'message' => "Sorry! Only {$item->variant->stock} items are currently available for {$item->product->name}.",
                        ], 400);
                    }
                }
            }

            $subtotal = $cartItems->sum('total');
            $shippingFee = 0;
            $tax = 0;
            $total = $subtotal + $shippingFee + $tax;

            $orderNumber = Order::generateOrderNumber();

            session([
                'pending_order_' . $orderNumber => [
                    'cart_items' => $cartItems->toArray(),
                    'is_single_item' => $isSingleItem,
                    'shipping_address' => $request->shipping_address,
                    'billing_address' => $request->billing_address ?? $request->shipping_address,
                    'notes' => $request->notes,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'tax' => $tax,
                    'total' => $total,
                    'user_id' => $userId,
                ],
            ]);

            Log::info('Cashfree Payment Request', [
                'app_id' => $this->appId,
                'base_url' => $this->baseUrl,
                'api_version' => $this->apiVersion,
            ]);

            $origin = $request->header('Origin') ?? env('FRONTEND_URL', 'http://localhost:3000');

            if (str_contains($this->baseUrl, 'sandbox')) {
                $returnUrl = $origin . "/checkout?order_id={order_id}";
            } else {
                if (str_starts_with($origin, 'http://localhost') || str_starts_with($origin, 'http://127.0.0.1')) {
                    $returnUrl = "https://yourdomain.com/checkout?order_id={order_id}";
                    Log::warning('Using placeholder HTTPS URL for development with production Cashfree keys');
                } else {
                    $returnUrl = str_replace('http://', 'https://', $origin) . "/checkout?order_id={order_id}";
                }
            }

            $response = Http::withoutVerifying()->withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/orders', [
                'order_id' => $orderNumber,
                'order_amount' => (float)$total,
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => (string)$userId,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->phone_number ?? '9999999999',
                    'customer_name' => $user->name,
                ],
                'order_meta' => [
                    'return_url' => $returnUrl,
                ]
            ]);

            Log::info('Cashfree Response Status: ' . $response->status());
            Log::info('Cashfree Response Body: ' . $response->body());

            if ($response->successful()) {
                $paymentData = $response->json();

                return response()->json([
                    'success' => true,
                    'payment_session_id' => $paymentData['payment_session_id'],
                    'order_number' => $orderNumber,
                ]);
            } else {
                Log::error('Cashfree Order Creation Failed', ['response' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initiate payment gateway. Please try again.',
                    'error' => $response->json(),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Payment Initiation Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        $orderNumber = $request->order_id;

        try {
            DB::beginTransaction();

            $response = Http::withoutVerifying()->withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => $this->apiVersion,
            ])->get($this->baseUrl . "/orders/{$orderNumber}");

            if ($response->successful()) {
                $orderData = $response->json();
                $orderStatus = $orderData['order_status'];

                $order = Order::where('order_number', $orderNumber)->first();

                if ($orderStatus === 'PAID') {
                    if ($order && $order->payment_status === 'paid') {
                        DB::commit();
                        return response()->json([
                            'success' => true,
                            'status' => $orderStatus,
                            'data' => $order,
                        ]);
                    }

                    $pendingOrderData = session('pending_order_' . $orderNumber);

                    if (!$pendingOrderData) {
                        DB::rollback();
                        Log::error('Pending order data not found in session', ['order_number' => $orderNumber]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Order session expired. Please contact support.',
                        ], 400);
                    }

                    $cartItems = collect($pendingOrderData['cart_items']);
                    foreach ($cartItems as $item) {
                        $variant = Variant::find($item['variant_id']);
                        if (!$variant || $variant->stock < $item['quantity']) {
                            DB::rollback();
                            Log::error('Stock validation failed during payment verification', [
                                'order_number' => $orderNumber,
                                'variant_id' => $item['variant_id'],
                                'requested' => $item['quantity'],
                                'available' => $variant ? $variant->stock : 0,
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => 'Product is out of stock. Your payment will be refunded within 5-7 business days.',
                            ], 400);
                        }
                    }

                    if (!$order) {
                        $order = Order::create([
                            'order_number' => $orderNumber,
                            'user_id' => $pendingOrderData['user_id'],
                            'status' => 'confirmed',
                            'payment_method' => 'online',
                            'payment_status' => 'paid',
                            'subtotal' => $pendingOrderData['subtotal'],
                            'shipping_fee' => $pendingOrderData['shipping_fee'],
                            'tax' => $pendingOrderData['tax'],
                            'total' => $pendingOrderData['total'],
                            'shipping_address' => $pendingOrderData['shipping_address'],
                            'billing_address' => $pendingOrderData['billing_address'],
                            'notes' => $pendingOrderData['notes'],
                            'transaction_id' => $orderData['cf_order_id'] ?? null,
                        ]);

                        foreach ($cartItems as $cartItem) {
                            OrderItem::create([
                                'order_id' => $order->id,
                                'product_id' => $cartItem['product_id'],
                                'variant_id' => $cartItem['variant_id'],
                                'quantity' => $cartItem['quantity'],
                                'price' => $cartItem['price'],
                                'total' => $cartItem['total'],
                                'selected_attributes' => $cartItem['selected_attributes'] ?? null,
                            ]);

                            Variant::where('id', $cartItem['variant_id'])
                                ->decrement('stock', $cartItem['quantity']);
                        }

                        $order->addTracking('confirmed', 'Payment successful', 'Online Store');

                        Cart::where('user_id', $order->user_id)->delete();

                        session()->forget('pending_order_' . $orderNumber);

                        Log::info('Order created successfully after payment verification', [
                            'order_number' => $orderNumber,
                            'order_id' => $order->id,
                        ]);
                    } else {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'confirmed',
                            'transaction_id' => $orderData['cf_order_id'] ?? null,
                        ]);
                        $order->addTracking('confirmed', 'Payment successful', 'Online Store');
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'status' => $orderStatus,
                        'data' => $order->load(['orderItems.product', 'orderItems.variant']),
                    ]);
                } else if ($orderStatus === 'ACTIVE') {
                    DB::commit();
                    return response()->json([
                        'success' => false,
                        'status' => $orderStatus,
                        'message' => 'Payment is still being processed. Please wait.',
                    ]);
                } else {
                    session()->forget('pending_order_' . $orderNumber);

                    DB::commit();

                    Log::info('Payment failed or cancelled', [
                        'order_number' => $orderNumber,
                        'status' => $orderStatus,
                    ]);

                    return response()->json([
                        'success' => false,
                        'status' => $orderStatus,
                        'message' => 'Payment was not successful. Please try again.',
                    ]);
                }
            } else {
                DB::rollback();
                Log::error('Failed to verify payment with Cashfree', [
                    'order_number' => $orderNumber,
                    'response' => $response->body(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify payment. Please contact support.',
                    'error' => $response->json(),
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment Verification Error', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment. Please contact support.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testCredentials()
    {
        try {
            return response()->json([
                'app_id' => $this->appId,
                'secret_key_preview' => substr($this->secretKey, 0, 10) . '...',
                'api_version' => $this->apiVersion,
                'base_url' => $this->baseUrl,
                'credentials_loaded' => !empty($this->appId) && !empty($this->secretKey),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
