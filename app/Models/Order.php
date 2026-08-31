<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'buyer_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'city',
        'neighborhood',
        'note',
        'payment_method',
        'payment_phone',
        'total',
        'status',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}