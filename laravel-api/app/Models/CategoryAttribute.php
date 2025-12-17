<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryAttribute extends Pivot
{
    protected $table = 'category_attributes';
    public $incrementing = false;
    public $timestamps   = false;

    protected $fillable = [
        'category_id',
        'attribute_id',
        'has_images',
        'is_primary',
    ];

    protected $casts = [
        'has_images' => 'boolean',
        'is_primary' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeForAttribute($query, $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    public static function existsFor($categoryId, $attributeId): bool
    {
        return static::where('category_id', $categoryId)
            ->where('attribute_id', $attributeId)
            ->exists();
    }
}
