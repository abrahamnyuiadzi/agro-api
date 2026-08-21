<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * Liste des producteurs
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getProducers()
        ]);
    }

    /**
     * Créer un producteur
     */
    public function storeProducer(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|unique:users,phone',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        $producer = $this->service->createProducer($validated);

        return response()->json([
            'success' => true,
            'message' => 'Producteur créé avec succès.',
            'data' => $producer
        ], 201);
    }

    /**
     * Afficher un producteur
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Modifier un producteur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'phone'      => 'sometimes|string|unique:users,phone,' . $user->id,
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:8|confirmed',
        ]);

        $producer = $this->service->updateProducer($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Producteur modifié avec succès.',
            'data' => $producer
        ]);
    }

    /**
     * Supprimer un producteur
     */
    public function destroy(User $user)
    {
        $this->service->deleteProducer($user);

        return response()->json([
            'success' => true,
            'message' => 'Producteur supprimé avec succès.'
        ]);
    }
}