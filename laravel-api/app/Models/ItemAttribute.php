<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAttribute extends Model
{
    protected $table = 'item_attributes';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'has_images',
        'is_primary',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id')->withDefault();
    }
}
