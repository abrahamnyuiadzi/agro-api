<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Liste des produits
     */
   public function index()
{
    $products = Product::with([
        'farm',
        'category'
    ])
    ->latest()
    ->paginate(10);

    return response()->json([
        'success' => true,
        'message' => 'Liste des produits',
        'data' => $products
    ]);
}

    /**
     * Création d'un produit
     */
public function store(Request $request)
{

    if($request->user()->role !== 'producer')
    {
        return response()->json([
            'message'=>'Seuls les producteurs peuvent créer des produits'
        ],403);
    }



    $validated = $request->validate([

        'farm_id'=>'required|exists:farms,id',

        'category_id'=>'required|exists:categories,id',

        'name'=>'required|string|max:255',

        'description'=>'required|string',

        'price'=>'required|numeric',

        'quantity'=>'required|integer',

        'unit'=>'required|string|max:50',

        'image'=>'required|image|mimes:jpeg,png,jpg,webp|max:2048'

    ]);



    // Upload image

    if($request->hasFile('image'))
    {

        $path = $request->file('image')
            ->store('products','public');


        $validated['image']=$path;

    }



    $validated['user_id']=$request->user()->id;



    $product = Product::create($validated);



    return response()->json([

        'success'=>true,

        'message'=>'Produit créé avec succès',

        'data'=>$product

    ],201);

}

    /**
     * Afficher un produit
     */
public function show(Product $product)
{
    $product->load([
        'category',
        'farm.owner',
    ]);

    return response()->json([
        'success' => true,
        'data' => $product,
    ]);
}

    /**
     * Modifier un produit
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'farm_id' => 'sometimes|exists:farms,id',
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:1',
            'unit' => 'sometimes|string|max:50',
            'is_available' => 'sometimes|boolean'
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour',
            'data' => $product
        ]);
    }

    /**
     * Supprimer un produit
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès'
        ]);
    }

public function myProducts(Request $request)
{
    $user = $request->user();

    $products = Product::with([
        'farm',
        'category'
    ])
    ->where('user_id', $user->id)
    ->latest()
    ->paginate(10);

    return response()->json([
        'success' => true,
        'message' => 'Liste de vos produits',
        'data' => $products,
    ]);
}

/**
 * Liste de tous les produits pour l'administration
 */
public function adminIndex()
{
    $products = Product::with([
        'farm',
        'category',
        'user'
    ])
    ->latest()
    ->paginate(10);

    return response()->json([
        'success' => true,
        'data' => $products
    ]);
}

/**
 * Supprimer un produit depuis l'administration
 */
/**
 * Supprimer un produit depuis l'administration
 */
public function adminDestroy(Product $product)
{
    try {

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès.'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Impossible de supprimer ce produit.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}