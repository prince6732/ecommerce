<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'sku',
        'stock',
        'mrp',
        'sp',
        'bp',
        'image_url',
        'image_json',
        'product_id',
        'status',
    ];

    protected $casts = [
        'image_json' => 'array',
        'status'     => 'boolean',
        'mrp'        => 'decimal:2',
        'sp'         => 'decimal:2',
        'bp'         => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'variant_attribute_values',
            'variant_id',
            'attribute_value_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sp ?? $this->mrp;
    }

    public function syncAttributeValues(array $attributeValueIds)
    {
        return $this->attributeValues()->sync($attributeValueIds);
    }
}
