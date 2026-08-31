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
     * Cette route est publique :
     * - un utilisateur connecté peut commander
     * - un visiteur peut commander sans compte
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation des informations de l'acheteur
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            // Informations personnelles
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|max:20',
            'email'      => 'nullable|email|max:255',

            // Adresse de livraison
            'address'      => 'required|string|max:500',
            'city'         => 'required|string|max:100',
            'neighborhood' => 'nullable|string|max:255',
            'note'         => 'nullable|string|max:1000',

            // Produits
            'items' => 'required|array|min:1',

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            // Paiement
            'payment_method' => [
                'required',
                'in:flooz,tmoney',
            ],

            'payment_phone' => [
                'required',
                'string',
                'max:20',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | 2. Récupérer l'utilisateur connecté
        |--------------------------------------------------------------------------
        |
        | La route étant publique, $request->user() peut être null.
        |
        */

        $buyerId = $request->user()?->id;


        /*
        |--------------------------------------------------------------------------
        | 3. Transaction
        |--------------------------------------------------------------------------
        |
        | Toute la création de la commande se fait dans une transaction.
        |
        | Si une erreur arrive :
        | - la commande n'est pas créée
        | - les order_items ne sont pas créés
        | - le stock n'est pas modifié
        |
        */

        try {

            $order = DB::transaction(function () use (
                $validated,
                $buyerId
            ) {

                /*
                |--------------------------------------------------------------------------
                | Total de la commande
                |--------------------------------------------------------------------------
                */

                $total = 0;

                /*
                |--------------------------------------------------------------------------
                | Tableau contenant les lignes de commande
                |--------------------------------------------------------------------------
                */

                $orderItems = [];


                /*
                |--------------------------------------------------------------------------
                | 4. Vérifier chaque produit
                |--------------------------------------------------------------------------
                */

                foreach ($validated['items'] as $item) {

                    /*
                    |--------------------------------------------------------------------------
                    | Récupérer le produit avec verrouillage
                    |--------------------------------------------------------------------------
                    |
                    | lockForUpdate() empêche deux commandes simultanées
                    | de modifier le même stock au même moment.
                    |
                    */

                    $product = Product::lockForUpdate()
                        ->find($item['product_id']);


                    /*
                    |--------------------------------------------------------------------------
                    | Produit inexistant
                    |--------------------------------------------------------------------------
                    */

                    if (!$product) {

                        throw new \Exception(
                            "Le produit #{$item['product_id']} n'existe plus."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Vérifier si le produit est disponible
                    |--------------------------------------------------------------------------
                    */

                    if (!$product->is_available) {

                        throw new \Exception(
                            "Le produit \"{$product->name}\" n'est plus disponible."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Quantité demandée
                    |--------------------------------------------------------------------------
                    */

                    $quantity = (int) $item['quantity'];


                    /*
                    |--------------------------------------------------------------------------
                    | Vérifier le stock
                    |--------------------------------------------------------------------------
                    */

                    if ($product->quantity < $quantity) {

                        throw new \Exception(
                            "Stock insuffisant pour le produit \"{$product->name}\". "
                            . "Stock disponible : {$product->quantity}."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Prix réel du produit
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT :
                    | On ne récupère PAS le prix envoyé par React.
                    |
                    | Le prix utilisé vient directement de la base de données.
                    |
                    */

                    $unitPrice = (float) $product->price;


                    /*
                    |--------------------------------------------------------------------------
                    | Calcul du sous-total
                    |--------------------------------------------------------------------------
                    */

                    $subtotal = $unitPrice * $quantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Ajouter au total
                    |--------------------------------------------------------------------------
                    */

                    $total += $subtotal;


                    /*
                    |--------------------------------------------------------------------------
                    | Préparer la ligne de commande
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

                    $product->quantity =
                        $product->quantity - $quantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Si le stock arrive à zéro
                    |--------------------------------------------------------------------------
                    */

                    if ($product->quantity <= 0) {

                        $product->quantity = 0;

                        $product->is_available = false;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Sauvegarder le nouveau stock
                    |--------------------------------------------------------------------------
                    */

                    $product->save();
                }


                /*
                |--------------------------------------------------------------------------
                | 5. Créer la commande
                |--------------------------------------------------------------------------
                */

                $order = Order::create([

                    // Utilisateur connecté ou NULL pour un visiteur
                    'buyer_id' => $buyerId,

                    // Informations de l'acheteur
                    'first_name' => $validated['first_name'],

                    'last_name' => $validated['last_name'],

                    'phone' => $validated['phone'],

                    'email' => $validated['email'] ?? null,


                    // Adresse
                    'address' => $validated['address'],

                    'city' => $validated['city'],

                    'neighborhood' =>
                        $validated['neighborhood'] ?? null,

                    'note' =>
                        $validated['note'] ?? null,


                    // Paiement
                    'payment_method' =>
                        $validated['payment_method'],

                    'payment_phone' =>
                        $validated['payment_phone'],


                    // Total calculé côté serveur
                    'total' => $total,


                    // Statut initial
                    'status' => 'pending',
                ]);


                /*
                |--------------------------------------------------------------------------
                | 6. Créer les order_items
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


                /*
                |--------------------------------------------------------------------------
                | Retourner la commande
                |--------------------------------------------------------------------------
                */

                return $order;
            });


            /*
            |--------------------------------------------------------------------------
            | 7. Charger les informations de la commande
            |--------------------------------------------------------------------------
            */

            $order->load([
                'items.product',
                'buyer',
            ]);


            /*
            |--------------------------------------------------------------------------
            | 8. Réponse succès
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' =>
                    'Votre commande a été reçue avec succès.',

                'data' => $order,

            ], 201);


        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | 9. Erreur
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
     * Liste des commandes de l'acheteur connecté
     */
    public function myOrders(Request $request)
    {
        $orders = Order::with([
            'items.product',
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
     *
     * Réservée à l'administrateur.
     */
    public function index()
    {
        $orders = Order::with([
            'items.product',
            'buyer',
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
            'items.product',
        ])
            ->whereHas('items.product', function ($query) use ($producerId) {

                $query->where('user_id', $producerId);

            })
            ->latest()
            ->paginate(10);


        return response()->json([

            'success' => true,

            'message' =>
                'Commandes concernant vos produits.',

            'data' => $orders,

        ]);
    }
}
