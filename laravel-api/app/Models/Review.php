<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'review_text',
        'title',
        'is_verified',
        'is_approved',
        'helpful_votes',
        'helpful_count'
    ];

    protected $casts = [
        'helpful_votes' => 'array',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'rating' => 'integer',
        'helpful_count' => 'integer'
    ];

    protected $appends = ['time_ago', 'user_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeOrderByHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'Anonymous';
    }

    public function isHelpfulForUser($userId)
    {
        $helpfulVotes = $this->helpful_votes ?? [];
        return in_array($userId, $helpfulVotes);
    }

    public function markAsHelpful($userId)
    {
        $helpfulVotes = $this->helpful_votes ?? [];

        if (!in_array($userId, $helpfulVotes)) {
            $helpfulVotes[] = $userId;
            $this->helpful_votes = $helpfulVotes;
            $this->helpful_count = count($helpfulVotes);
            $this->save();
        }
    }

    public function unmarkAsHelpful($userId)
    {
        $helpfulVotes = $this->helpful_votes ?? [];

        if (($key = array_search($userId, $helpfulVotes)) !== false) {
            unset($helpfulVotes[$key]);
            $this->helpful_votes = array_values($helpfulVotes);
            $this->helpful_count = count($this->helpful_votes);
            $this->save();
        }
    }

    public static function getRatingDistribution($productId)
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = self::where('product_id', $productId)
                ->approved()
                ->where('rating', $i)
                ->count();
        }
        return $distribution;
    }
}
