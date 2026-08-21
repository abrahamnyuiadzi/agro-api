<?php

use App\Models\Product;

class ProductRepository
{
    public function all()
    {
        return Product::query()
            ->with([
                'category',
                'farm',
                'images'
            ])
            ->paginate();
    }
}