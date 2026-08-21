<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Liste des producteurs
     */
    public function getProducers()
    {
        return User::where('role', 'producer')
            ->latest()
            ->paginate(10);
    }

    /**
     * Trouver un utilisateur
     */
    public function findById($id)
    {
        return User::findOrFail($id);
    }

    /**
     * Créer un utilisateur
     */
    public function create(array $data)
    {
        return User::create($data);
    }

    /**
     * Modifier un utilisateur
     */
    public function update(User $user, array $data)
    {
        $user->update($data);

        return $user;
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete(User $user)
    {
        return $user->delete();
    }
}