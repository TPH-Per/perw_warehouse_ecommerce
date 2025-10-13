<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    /**
     * Get authenticated user ID
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }
        return $userId;
    }

    /**
     * Display reviews for a specific product
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            $query = ProductReview::where('product_id', $productId)
                                 ->where('status', 'approved')
                                 ->with('user:id,full_name');

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Filter by rating
            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $reviews = $query->paginate($perPage);

            // Calculate review statistics
            $stats = ProductReview::where('product_id', $productId)
                                 ->where('status', 'approved')
                                 ->selectRaw('
                                     AVG(rating) as average_rating,
                                     COUNT(*) as total_reviews,
                                     SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                                     SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                                     SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                                     SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                                     SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                                 ')
                                 ->first();

            return response()->json([
                'reviews' => $reviews,
                'statistics' => [
                    'average_rating' => round($stats->average_rating ?? 0, 1),
                    'total_reviews' => $stats->total_reviews ?? 0,
                    'rating_breakdown' => [
                        5 => $stats->five_star ?? 0,
                        4 => $stats->four_star ?? 0,
                        3 => $stats->three_star ?? 0,
                        2 => $stats->two_star ?? 0,
                        1 => $stats->one_star ?? 0,
                    ]
                ],
                'message' => 'Reviews retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found.',
                'errors' => ['product' => ['The requested product does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve reviews.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created review (Authenticated users only)
     */
    public function store(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $userId = $this->getAuthenticatedUserId();

            // Check if user already reviewed this product
            $existingReview = ProductReview::where('product_id', $productId)
                                         ->where('user_id', $userId)
                                         ->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'You have already reviewed this product.',
                    'errors' => ['review' => ['You can only submit one review per product.']]
                ], 422);
            }

            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:1000',
            ]);

            $review = ProductReview::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'status' => 'pending', // Reviews need approval
            ]);

            return response()->json([
                'review' => $review->load('user:id,full_name'),
                'message' => 'Review submitted successfully and is pending approval.'
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found.',
                'errors' => ['product' => ['The requested product does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit review.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update user's own review
     */
    public function update(Request $request, int $reviewId): JsonResponse
    {
        try {
            $review = ProductReview::where('id', $reviewId)
                                  ->where('user_id', $this->getAuthenticatedUserId())
                                  ->firstOrFail();

            $request->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'review_text' => 'sometimes|nullable|string|max:1000',
            ]);

            $review->update([
                'rating' => $request->get('rating', $review->rating),
                'review_text' => $request->get('review_text', $review->review_text),
                'status' => 'pending', // Re-submit for approval after edit
            ]);

            return response()->json([
                'review' => $review->load('user:id,full_name'),
                'message' => 'Review updated successfully and is pending approval.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Review not found or you do not have permission to edit it.',
                'errors' => ['review' => ['The requested review does not exist or belongs to another user.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update review.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Delete user's own review
     */
    public function destroy(int $reviewId): JsonResponse
    {
        try {
            $review = ProductReview::where('id', $reviewId)
                                  ->where('user_id', $this->getAuthenticatedUserId())
                                  ->firstOrFail();

            $review->delete();

            return response()->json([
                'message' => 'Review deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Review not found or you do not have permission to delete it.',
                'errors' => ['review' => ['The requested review does not exist or belongs to another user.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete review.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get user's own reviews
     */
    public function myReviews(Request $request): JsonResponse
    {
        try {
            $query = ProductReview::where('user_id', $this->getAuthenticatedUserId())
                                 ->with('product:id,name,slug');

            // Pagination
            $perPage = $request->get('per_page', 10);
            $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'reviews' => $reviews,
                'message' => 'Your reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve your reviews.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}