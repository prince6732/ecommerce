<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request, $productId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $rating = $request->get('rating');
            $sortBy = $request->get('sort_by', 'newest');

            $query = Review::with('user:id,name,profile_picture')
                ->where('product_id', $productId)
                ->approved();

            if ($rating) {
                $query->byRating($rating);
            }

            switch ($sortBy) {
                case 'oldest':
                    $query->oldest();
                    break;
                case 'highest':
                    $query->orderBy('rating', 'desc')->latest();
                    break;
                case 'lowest':
                    $query->orderBy('rating', 'asc')->latest();
                    break;
                case 'helpful':
                    $query->orderByHelpful()->latest();
                    break;
                default:
                    $query->latest();
                    break;
            }

            $reviews = $query->paginate($perPage);

            $totalReviews = Review::where('product_id', $productId)->approved()->count();
            $averageRating = Review::where('product_id', $productId)->approved()->avg('rating');
            $ratingDistribution = Review::getRatingDistribution($productId);

            return response()->json([
                'res' => 'success',
                'reviews' => $reviews,
                'summary' => [
                    'total_reviews' => $totalReviews,
                    'average_rating' => round($averageRating, 1),
                    'rating_distribution' => $ratingDistribution
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to fetch reviews'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:1000',
                'title' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();

            $existingReview = Review::where('user_id', $userId)
                ->where('product_id', $request->product_id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'You have already reviewed this product'
                ], 409);
            }

            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            // TODO: Check if user has purchased this product (optional)
            // $hasPurchased = $this->checkUserPurchase($userId, $request->product_id);

            $review = Review::create([
                'user_id' => $userId,
                'product_id' => $request->product_id,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'title' => $request->title,
                'is_verified' => false,
                'is_approved' => true,
            ]);

            return response()->json([
                'res' => 'success',
                'message' => 'Review submitted successfully',
                'review' => $review->load('user:id,name,profile_picture')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to submit review'
            ], 500);
        }
    }

    public function update(Request $request, $reviewId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:1000',
                'title' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $review = Review::find($reviewId);

            if (!$review) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Review not found'
                ], 404);
            }

            if ($review->user_id !== Auth::id()) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Unauthorized to update this review'
                ], 403);
            }

            $review->update([
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'title' => $request->title,
            ]);

            return response()->json([
                'res' => 'success',
                'message' => 'Review updated successfully',
                'review' => $review->load('user:id,name,profile_picture')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to update review'
            ], 500);
        }
    }

    public function destroy($reviewId): JsonResponse
    {
        try {
            $review = Review::find($reviewId);

            if (!$review) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Review not found'
                ], 404);
            }

            if ($review->user_id !== Auth::id()) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Unauthorized to delete this review'
                ], 403);
            }

            $review->delete();

            return response()->json([
                'res' => 'success',
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to delete review'
            ], 500);
        }
    }

    public function toggleHelpful(Request $request, $reviewId): JsonResponse
    {
        try {
            $review = Review::find($reviewId);

            if (!$review) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Review not found'
                ], 404);
            }

            $userId = Auth::id();

            if ($review->isHelpfulForUser($userId)) {
                $review->unmarkAsHelpful($userId);
                $action = 'unmarked';
            } else {
                $review->markAsHelpful($userId);
                $action = 'marked';
            }

            return response()->json([
                'res' => 'success',
                'message' => "Review {$action} as helpful",
                'helpful_count' => $review->helpful_count,
                'is_helpful' => $action === 'marked'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to update helpful status'
            ], 500);
        }
    }

    public function getUserReview($productId): JsonResponse
    {
        try {
            $userId = Auth::id();

            $review = Review::where('user_id', $userId)
                ->where('product_id', $productId)
                ->with('user:id,name,profile_picture')
                ->first();

            return response()->json([
                'res' => 'success',
                'review' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to fetch user review'
            ], 500);
        }
    }

    private function checkUserPurchase($userId, $productId): bool
    {
        // TODO: Implement based on your order/purchase system
        // Example:
        // return Order::where('user_id', $userId)
        //     ->whereHas('items', function($query) use ($productId) {
        //         $query->where('product_id', $productId);
        //     })
        //     ->where('status', 'completed')
        //     ->exists();

        return true; // For now, assume all users can review
    }
}
