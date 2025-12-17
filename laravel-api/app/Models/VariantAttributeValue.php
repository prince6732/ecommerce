<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VariantAttributeValue extends Pivot
{
    protected $table = 'variant_attribute_values';

    public $incrementing = false;
    public $timestamps   = false;

    protected $fillable = [
        'variant_id',
        'attribute_value_id',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function scopeForVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeForAttributeValue($query, $attributeValueId)
    {
        return $query->where('attribute_value_id', $attributeValueId);
    }

    public static function existsFor($variantId, $attributeValueId): bool
    {
        return static::where('variant_id', $variantId)
            ->where('attribute_value_id', $attributeValueId)
            ->exists();
    }
}
