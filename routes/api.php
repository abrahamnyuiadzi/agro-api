<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

// Authentification Buyer
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Produits
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Catégories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Exploitations
Route::get('/farms', [FarmController::class, 'index']);
Route::get('/farms/{farm}', [FarmController::class, 'show']);

// Achat sans connexion (Guest Checkout)
Route::post('/orders', [OrderController::class, 'store']);


/*
|--------------------------------------------------------------------------
| Utilisateur connecté (Buyer, Producer, Admin)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

});


/*
|--------------------------------------------------------------------------
| Buyer
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'buyer'])->group(function () {

    // Profil
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // Historique des commandes
    Route::get('/my-orders', [OrderController::class, 'myOrders']);

});


/*
|--------------------------------------------------------------------------
| Producer
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'producer'])->group(function () {

    // Exploitations
    Route::get('/producer/farms', [FarmController::class, 'myFarms']);
    Route::post('/farms', [FarmController::class, 'store']);
    Route::put('/farms/{farm}', [FarmController::class, 'update']);
    Route::delete('/farms/{farm}', [FarmController::class, 'destroy']);

    // Produits
    Route::get('/producer/products', [ProductController::class, 'myProducts']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Commandes reçues
    Route::get('/producer/orders', [OrderController::class, 'producerOrders']);

});


/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Gestion des producteurs
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    Route::post('/producers', [UserController::class, 'storeProducer']);

    Route::put('/users/{user}', [UserController::class, 'update']);
    
    Route::delete('/products/{product}', [ProductController::class, 'adminDestroy']);

    Route::delete('/users/{user}', [UserController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | Catégories
    |--------------------------------------------------------------------------
    */

    Route::post('/categories', [CategoryController::class, 'store']);

    Route::put('/categories/{category}', [CategoryController::class, 'update']);

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | Gestion globale des produits
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/products', [ProductController::class, 'adminIndex']);

    Route::delete('/admin/products/{product}', [ProductController::class, 'adminDestroy']);


    /*
    |--------------------------------------------------------------------------
    | Gestion des exploitations
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/farms', [FarmController::class, 'adminIndex']);


    /*
    |--------------------------------------------------------------------------
    | Gestion des commandes
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/orders', [OrderController::class, 'index']);

});