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
        'total',
        'status',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    /**
     * Acheteur connecté
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Produits de la commande
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
