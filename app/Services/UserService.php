<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Liste des producteurs
     */
    public function getProducers()
    {
        return $this->repository->getProducers();
    }

    /**
     * Créer un producteur
     */
    public function createProducer(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $data['role'] = 'producer';

        return $this->repository->create($data);
    }

    /**
     * Modifier un producteur
     */
    public function updateProducer(User $user, array $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {

            $data['password'] = Hash::make($data['password']);

        } else {

            unset($data['password']);

        }

        return $this->repository->update($user, $data);
    }

    /**
     * Supprimer un producteur
     */
    public function deleteProducer(User $user)
    {
        return $this->repository->delete($user);
    }
}