<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        try {
            $cartItems = Cart::with(['product.brand', 'product.category', 'variant'])
                ->where('user_id', Auth::id())
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                        'selected_attributes' => $item->selected_attributes,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image_url' => $item->product->image_url,
                            'brand' => $item->product->brand ? $item->product->brand->name : null,
                            'category_name' => $item->product->category ? $item->product->category->name : null,
                        ],
                        'variant' => [
                            'id' => $item->variant->id,
                            'title' => $item->variant->title,
                            'sku' => $item->variant->sku,
                            'stock' => $item->variant->stock,
                            'image_url' => $item->variant->image_url,
                            'bs' => $item->variant->bs ?? null,
                            'mrp' => $item->variant->mrp ?? null,
                            'sp' => $item->variant->sp ?? $item->price,
                        ],
                    ];
                });

            $cartTotal = $cartItems->sum('total');
            $cartCount = $cartItems->sum('quantity');

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $cartItems,
                    'total' => $cartTotal,
                    'count' => $cartCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cart items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'variant_id' => 'required|exists:variants,id',
                'quantity' => 'required|integer|min:1',
                'selected_attributes' => 'nullable|array',
            ]);

            $product = Product::findOrFail($request->product_id);
            $variant = Variant::findOrFail($request->variant_id);

            if ($variant->product_id !== $product->id) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Selected variant does not belong to the specified product.',
                ]);
            }

            if ($variant->stock < $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock available. Only ' . $variant->stock . ' items left.',
                ]);
            }

            DB::beginTransaction();

            $existingCartItem = Cart::where([
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
            ])->first();

            if ($existingCartItem) {
                $newQuantity = $existingCartItem->quantity + $request->quantity;

                if ($newQuantity > $variant->stock) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Total quantity would exceed available stock (' . $variant->stock . ' available).',
                    ]);
                }

                $existingCartItem->update([
                    'quantity' => $newQuantity,
                    'price' => $variant->sp,
                    'selected_attributes' => $request->selected_attributes,
                ]);

                $cartItem = $existingCartItem;
            } else {
                $cartItem = Cart::create([
                    'user_id' => Auth::id(),
                    'product_id' => $request->product_id,
                    'variant_id' => $request->variant_id,
                    'quantity' => $request->quantity,
                    'price' => $variant->sp,
                    'selected_attributes' => $request->selected_attributes,
                ]);
            }

            DB::commit();

            $cartItem->load(['product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->variant_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total' => $cartItem->total,
                    'product' => [
                        'id' => $cartItem->product->id,
                        'name' => $cartItem->product->name,
                        'image_url' => $cartItem->product->image_url,
                    ],
                    'variant' => [
                        'id' => $cartItem->variant->id,
                        'title' => $cartItem->variant->title,
                        'sku' => $cartItem->variant->sku,
                        'stock' => $cartItem->variant->stock,
                    ],
                ],
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $cartItem = Cart::where([
                'id' => $id,
                'user_id' => Auth::id(),
            ])->firstOrFail();

            $variant = $cartItem->variant;

            if ($variant->stock < $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock available. Only ' . $variant->stock . ' items left.',
                ]);
            }

            $cartItem->update([
                'quantity' => $request->quantity,
                'price' => $variant->sp,
            ]);

            $cartItem->load(['product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'data' => [
                    'id' => $cartItem->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total' => $cartItem->total,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cartItem = Cart::where([
                'id' => $id,
                'user_id' => Auth::id(),
            ])->firstOrFail();

            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clear()
    {
        try {
            Cart::where('user_id', Auth::id())->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCartCount()
    {
        try {
            $count = Cart::where('user_id', Auth::id())->sum('quantity');

            return response()->json([
                'success' => true,
                'data' => ['count' => $count],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cart count',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
