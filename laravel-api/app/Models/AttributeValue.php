<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'value',
        'description',
        'attribute_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variants()
    {
        return $this->belongsToMany(
            Variant::class,
            'variant_attribute_values',
            'attribute_value_id',
            'variant_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
