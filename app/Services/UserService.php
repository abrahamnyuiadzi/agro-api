<?php

namespace App\Services;

use App\Models\User;
use App\Models\Farm;
use App\Models\Product;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
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
     * Supprimer un producteur avec ses fermes et ses produits
     */
    public function deleteProducer(User $user)
    {
        return DB::transaction(function () use ($user) {

            // Vérifier que l'utilisateur est bien un producteur
            if ($user->role !== 'producer') {
                throw new \Exception(
                    'Cet utilisateur n\'est pas un producteur.'
                );
            }

            /*
             * 1. Récupérer les fermes du producteur
             */
            $farms = Farm::where('user_id', $user->id)->get();

            /*
             * 2. Supprimer les produits du producteur
             */
            Product::where('user_id', $user->id)->delete();

            /*
             * 3. Supprimer les produits liés à ses fermes
             *
             * Cette partie est utile si certains produits
             * sont liés uniquement avec farm_id.
             */
            foreach ($farms as $farm) {
                Product::where('farm_id', $farm->id)->delete();
            }

            /*
             * 4. Supprimer les fermes
             */
            Farm::where('user_id', $user->id)->delete();

            /*
             * 5. Supprimer le producteur
             */
            $this->repository->delete($user);

            return true;
        });
    }
}