import axios from "./axios";

export interface Review {
    id: number;
    user_id: number;
    product_id: number;
    rating: number;
    review_text: string | null;
    title: string | null;
    is_verified: boolean;
    is_approved: boolean;
    helpful_count: number;
    time_ago: string;
    user_name: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        profile_picture?: string | null;
    };
}

export interface ReviewSummary {
    total_reviews: number;
    average_rating: number;
    rating_distribution: {
        [key: number]: number;
    };
}

export interface ReviewResponse {
    res: 'success' | 'error';
    reviews: {
        data: Review[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    summary: ReviewSummary;
    message?: string;
}

export interface CreateReviewData {
    product_id: number;
    rating: number;
    review_text?: string;
    title?: string;
}

export interface UpdateReviewData {
    rating: number;
    review_text?: string;
    title?: string;
}

export const getProductReviews = async (
    productId: number,
    params?: {
        page?: number;
        per_page?: number;
        rating?: number;
        sort_by?: 'newest' | 'oldest' | 'highest' | 'lowest' | 'helpful';
    }
): Promise<ReviewResponse> => {
    try {
        const response = await axios.get(`/api/products/${productId}/reviews`, { params });
        return response.data;
    } catch (error: any) {
        throw new Error(error.response?.data?.message || 'Failed to fetch reviews');
    }
};

export const createReview = async (reviewData: CreateReviewData): Promise<{
    res: 'success' | 'error';
    review?: Review;
    message: string;
}> => {
    try {
        const response = await axios.post('/api/reviews', reviewData);
        return response.data;
    } catch (error: any) {
        throw new Error(error.response?.data?.message || 'Failed to create review');
    }
};

export const updateReview = async (
    reviewId: number,
    reviewData: UpdateReviewData
): Promise<{
    res: 'success' | 'error';
    review?: Review;
    message: string;
}> => {
    try {
        const response = await axios.put(`/api/reviews/${reviewId}`, reviewData);
        return response.data;
    } catch (error: any) {
        throw new Error(error.response?.data?.message || 'Failed to update review');
    }
};

export const deleteReview = async (reviewId: number): Promise<{
    res: 'success' | 'error';
    message: string;
}> => {
    try {
        const response = await axios.delete(`/api/reviews/${reviewId}`);
        return response.data;
    } catch (error: any) {
        throw new Error(error.response?.data?.message || 'Failed to delete review');
    }
};

export const toggleReviewHelpful = async (reviewId: number): Promise<{
    res: 'success' | 'error';
    helpful_count: number;
    is_helpful: boolean;
    message: string;
}> => {
    try {
        const response = await axios.post(`/api/reviews/${reviewId}/helpful`);
        return response.data;
    } catch (error: any) {
        throw new Error(error.response?.data?.message || 'Failed to update helpful status');
    }
};

export const getUserProductReview = async (productId: number): Promise<{
    res: 'success' | 'error';
    review: Review | null;
}> => {
    try {
        const response = await axios.get(`/api/products/${productId}/my-review`);
        return response.data;
    } catch (error: any) {
        throw new Error(error.response?.data?.message || 'Failed to fetch user review');
    }
};