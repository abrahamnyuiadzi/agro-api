<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Str;

class CategoryService
{
    protected $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function find($id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        $data['slug'] = Str::slug($data['name']);

        return $this->repository->create($data);
    }

    public function update(Category $category, array $data)
    {
        $data['slug'] = Str::slug($data['name']);

        return $this->repository->update($category, $data);
    }

    public function delete(Category $category)
    {
        return $this->repository->delete($category);
    }
}