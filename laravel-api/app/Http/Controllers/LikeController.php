<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        try {
            DB::beginTransaction();

            $existingLike = Like::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if ($existingLike) {
                $existingLike->delete();
                $liked = false;
                $message = 'Product removed from wishlist';
            } else {
                Like::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                ]);
                $liked = true;
                $message = 'Product added to wishlist';
            }

            $likesCount = Like::where('product_id', $productId)->count();

            DB::commit();

            return response()->json([
                'res' => 'success',
                'message' => $message,
                'liked' => $liked,
                'likes_count' => $likesCount,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to update like status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserLikes()
    {
        try {
            $userId = Auth::id();

            $likedProducts = Product::whereHas('likes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->with([
                    'category',
                    'brand',
                    'variants' => function ($query) {
                        $query->orderBy('sp', 'asc')->take(1);
                    },
                    'likes' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ])
                ->get()
                ->map(function ($product) {
                    $product->is_liked = true;
                    $product->likes_count = $product->likes()->count();
                    $product->min_price = $product->variants->min('sp');
                    return $product;
                });

            return response()->json([
                'res' => 'success',
                'liked_products' => $likedProducts,
                'count' => $likedProducts->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to fetch liked products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkLikeStatus(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        $isLiked = Like::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();

        $likesCount = Like::where('product_id', $productId)->count();

        return response()->json([
            'res' => 'success',
            'is_liked' => $isLiked,
            'likes_count' => $likesCount,
        ]);
    }

    public function getProductsLikesStatus(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $userId = Auth::id();
        $productIds = $request->product_ids;

        try {
            $likesData = [];

            foreach ($productIds as $productId) {
                $isLiked = Like::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->exists();

                $likesCount = Like::where('product_id', $productId)->count();

                $likesData[$productId] = [
                    'is_liked' => $isLiked,
                    'likes_count' => $likesCount,
                ];
            }

            return response()->json([
                'res' => 'success',
                'likes_data' => $likesData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to fetch likes data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
