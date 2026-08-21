<?php

namespace App\Services;

use App\Models\Farm;
use App\Repositories\FarmRepository;

class FarmService
{

    protected $repository;


    public function __construct(FarmRepository $repository)
    {
        $this->repository=$repository;
    }



    public function getAll()
    {
        return $this->repository->getAll();
    }



    public function find(Farm $farm)
    {
        return $this->repository->find($farm);
    }



    public function create(array $data)
    {
        return $this->repository->create($data);
    }



    public function update(Farm $farm,array $data)
    {
        return $this->repository->update($farm,$data);
    }



    public function delete(Farm $farm)
    {
        return $this->repository->delete($farm);
    }


      public function getMine($userId)
{
    return $this->repository->getByUser($userId);
}
}