<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $fillable = [
        'title',
        'description',
        'image',
        'link',
        'open_in_new_tab',
        'status',
        'order',
    ];
}
