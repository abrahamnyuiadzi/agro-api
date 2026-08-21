<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    /**
     * Créer un produit
     */
    public function create(array $data)
    {
        return Product::create($data);
    }

    /**
     * Modifier un produit
     */
    public function update(Product $product, array $data)
    {
        $product->update($data);

        return $product;
    }

    /**
     * Récupérer les produits du producteur connecté
     */
    public function getMine($userId)
    {
        return Product::with([
            'farm',
            'category'
        ])
        ->where('user_id', $userId)
        ->latest()
        ->paginate(10);
    }
}