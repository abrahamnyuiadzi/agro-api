<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
     protected $fillable = [
        'user_id',
        'name',
        'description',
        'location',
        'city',
        'country',
        'surface',
        'type',
        'image',
        'is_verified'
    ];

       public function owner()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}
}
