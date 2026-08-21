<?php

namespace App\Repositories;

use App\Models\Farm;

class FarmRepository
{

    public function getAll()
    {
        return Farm::with('user')
            ->latest()
            (10);
    }



    public function find(Farm $farm)
    {
        return $farm->load([
            'user',
            'products'
        ]);
    }



    public function create(array $data)
    {
        return Farm::create($data);
    }



    public function update(Farm $farm,array $data)
    {
        $farm->update($data);

        return $farm;
    }



    public function delete(Farm $farm)
    {
        return $farm->delete();
    }

    public function getByUser($userId)
{
    return Farm::where('user_id', $userId)->get();
}

}