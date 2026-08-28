<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Créer une commande
     *
     * Cette route peut être utilisée :
     * - par un utilisateur connecté
     * - par un visiteur non connecté
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation des informations de l'acheteur
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',

            'phone' => 'required|string|max:20',

            'email' => 'nullable|email|max:255',

            'address' => 'required|string|max:500',

            'city' => 'required|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Produits commandés
            |--------------------------------------------------------------------------
            */

            'items' => 'required|array|min:1',

            'items.*.product_id' => 'required|integer|exists:products,id',

            'items.*.quantity' => 'required|integer|min:1',

            /*
            |--------------------------------------------------------------------------
            | Paiement
            |--------------------------------------------------------------------------
            */

            'payment_method' => 'required|in:flooz,tmoney',

            'payment_phone' => 'required|string|max:20',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Vérifier si l'utilisateur est connecté
        |--------------------------------------------------------------------------
        |
        | La route /orders est publique.
        | Si un utilisateur est connecté, on récupère son ID.
        | Sinon buyer_id sera NULL.
        |
        */

        $buyerId = $request->user()?->id;

        /*
        |--------------------------------------------------------------------------
        | 3. Transaction
        |--------------------------------------------------------------------------
        |
        | Toutes les opérations sont exécutées ensemble.
        | Si une erreur survient, tout est annulé.
        |
        */

        try {

            $order = DB::transaction(function () use (
                $validated,
                $buyerId
            ) {

                $total = 0;

                $orderItems = [];

                /*
                |--------------------------------------------------------------------------
                | 4. Vérification des produits et calcul du total
                |--------------------------------------------------------------------------
                */

                foreach ($validated['items'] as $item) {

                    /*
                    |--------------------------------------------------------------------------
                    | On verrouille le produit pendant la transaction
                    |--------------------------------------------------------------------------
                    |
                    | Cela évite que deux personnes achètent simultanément
                    | le dernier stock disponible.
                    |
                    */

                    $product = Product::lockForUpdate()
                        ->find($item['product_id']);

                    /*
                    |--------------------------------------------------------------------------
                    | Produit introuvable
                    |--------------------------------------------------------------------------
                    */

                    if (!$product) {
                        throw new \Exception(
                            "Le produit #{$item['product_id']} n'existe plus."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Vérifier que le produit est disponible
                    |--------------------------------------------------------------------------
                    */

                    if (!$product->is_available) {
                        throw new \Exception(
                            "Le produit \"{$product->name}\" n'est plus disponible."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Vérifier le stock
                    |--------------------------------------------------------------------------
                    */

                    if ($product->quantity < $item['quantity']) {

                        throw new \Exception(
                            "Stock insuffisant pour le produit \"{$product->name}\". " .
                            "Stock disponible : {$product->quantity}."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prix actuel du produit
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT :
                    | On ne fait jamais confiance au prix envoyé par React.
                    | Le prix vient directement de la base de données.
                    |
                    */

                    $unitPrice = (float) $product->price;

                    $quantity = (int) $item['quantity'];

                    $subtotal = $unitPrice * $quantity;

                    /*
                    |--------------------------------------------------------------------------
                    | Ajouter au total
                    |--------------------------------------------------------------------------
                    */

                    $total += $subtotal;

                    /*
                    |--------------------------------------------------------------------------
                    | Préparer le order_item
                    |--------------------------------------------------------------------------
                    */

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $unitPrice,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Diminuer le stock
                    |--------------------------------------------------------------------------
                    */

                    $product->quantity -= $quantity;

                    /*
                    |--------------------------------------------------------------------------
                    | Si le stock arrive à zéro
                    |--------------------------------------------------------------------------
                    */

                    if ($product->quantity <= 0) {

                        $product->quantity = 0;

                        $product->is_available = false;
                    }

                    $product->save();
                }

                /*
                |--------------------------------------------------------------------------
                | 5. Créer la commande
                |--------------------------------------------------------------------------
                */

                $order = Order::create([
                    'buyer_id' => $buyerId,

                    'first_name' => $validated['first_name'],

                    'last_name' => $validated['last_name'],

                    'phone' => $validated['phone'],

                    'email' => $validated['email'] ?? null,

                    'address' => $validated['address'],

                    'city' => $validated['city'],

                    'payment_method' => $validated['payment_method'],

                    'payment_phone' => $validated['payment_phone'],

                    'total' => $total,

                    'status' => 'pending',
                ]);

                /*
                |--------------------------------------------------------------------------
                | 6. Créer les lignes de commande
                |--------------------------------------------------------------------------
                */

                foreach ($orderItems as $item) {

                    OrderItem::create([
                        'order_id' => $order->id,

                        'product_id' => $item['product_id'],

                        'quantity' => $item['quantity'],

                        'price' => $item['price'],
                    ]);
                }

                return $order;
            });

            /*
            |--------------------------------------------------------------------------
            | 7. Recharger la commande avec ses produits
            |--------------------------------------------------------------------------
            */

            $order->load([
                'items.product',
                'buyer',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 8. Réponse
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' => 'Commande créée avec succès.',

                'data' => $order,
            ], 201);

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | En cas d'erreur
            |--------------------------------------------------------------------------
            |
            | La transaction est automatiquement annulée.
            |
            */

            return response()->json([
                'success' => false,

                'message' => $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Commandes de l'utilisateur connecté
     */
    public function myOrders(Request $request)
    {
        $orders = Order::with([
            'items.product'
        ])
        ->where('buyer_id', $request->user()->id)
        ->latest()
        ->paginate(10);

        return response()->json([
            'success' => true,

            'message' => 'Liste de vos commandes.',

            'data' => $orders,
        ]);
    }


    /**
     * Liste globale des commandes
     * Réservée à l'administrateur
     */
    public function index()
    {
        $orders = Order::with([
            'items.product',
            'buyer'
        ])
        ->latest()
        ->paginate(10);

        return response()->json([
            'success' => true,

            'message' => 'Liste des commandes.',

            'data' => $orders,
        ]);
    }


    /**
     * Commandes concernant les produits d'un producteur
     */
    public function producerOrders(Request $request)
    {
        $producerId = $request->user()->id;

        $orders = Order::with([
            'items.product'
        ])
        ->whereHas('items.product', function ($query) use ($producerId) {

            $query->where('user_id', $producerId);

        })
        ->latest()
        ->paginate(10);

        return response()->json([
            'success' => true,

            'message' => 'Commandes concernant vos produits.',

            'data' => $orders,
        ]);
    }
}
