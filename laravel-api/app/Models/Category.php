<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'secondary_image',
        'link',
        'parent_id',
        'status',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(
            Attribute::class,
            'category_attributes',
            'category_id',
            'attribute_id'
        )
            ->withPivot(['has_images', 'is_primary'])
            ->using(CategoryAttribute::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
