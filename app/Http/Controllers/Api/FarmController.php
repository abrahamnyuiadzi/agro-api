<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\FarmService;
use Illuminate\Http\Request;


class FarmController extends Controller
{

    protected $service;


    public function __construct(FarmService $service)
    {
        $this->service=$service;
    }



    public function index()
    {
        return response()->json([
            'success'=>true,
            'data'=>$this->service->getAll()
        ]);
    }



    public function show(Farm $farm)
    {
        return response()->json([
            'success'=>true,
            'data'=>$this->service->find($farm)
        ]);
    }




    public function store(Request $request)
    {

        if($request->user()->role !== 'producer')
        {
            return response()->json([
                'message'=>'Seuls les producteurs peuvent créer une exploitation'
            ],403);
        }



        $validated=$request->validate([

            'name'=>'required|string|max:255',

            'description'=>'nullable|string',

            'location'=>'required|string',

            'city'=>'nullable|string',

            'country'=>'nullable|string',

            'surface'=>'nullable|numeric',

            'type'=>'required|in:crop,livestock,mixed',

            'image'=>'nullable|image|max:2048'

        ]);



        $validated['user_id']=$request->user()->id;



        $farm=$this->service->create($validated);



        return response()->json([
            'success'=>true,
            'message'=>'Exploitation créée',
            'data'=>$farm
        ],201);

    }

    public function myFarms(Request $request)
{
    $farms = $this->service->getMine($request->user()->id);

    return response()->json([
        'success' => true,
        'message' => 'Liste de vos exploitations',
        'data' => $farms,
    ]);
}

public function update(Request $request, Farm $farm)
{
    if ($farm->user_id !== $request->user()->id) {
        return response()->json([
            'message' => "Vous ne pouvez modifier que vos propres exploitations."
        ], 403);
    }

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'location' => 'sometimes|required|string',
        'city' => 'nullable|string',
        'country' => 'nullable|string',
        'surface' => 'nullable|numeric',
        'type' => 'sometimes|required|in:crop,livestock,mixed',
        'image' => 'nullable|image|max:2048',
    ]);

    $updated = $this->service->update($farm, $validated);

    return response()->json([
        'success' => true,
        'message' => 'Exploitation mise à jour',
        'data' => $updated,
    ]);
}

public function destroy(Request $request, Farm $farm)
{
    if ($farm->user_id !== $request->user()->id) {
        return response()->json([
            'message' => "Vous ne pouvez supprimer que vos propres exploitations."
        ], 403);
    }

    $this->service->delete($farm);

    return response()->json([
        'success' => true,
        'message' => 'Exploitation supprimée',
    ]);
}


}


