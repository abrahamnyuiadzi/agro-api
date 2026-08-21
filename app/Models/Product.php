<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
      protected $fillable = [
        'user_id',
        'farm_id',
        'category_id',
        'name',
        'description',
        'image',
        'price',
        'quantity',
        'unit',
        'is_available'
    ];

        public function farm()
    {
        return $this->belongsTo(Farm::class);
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
