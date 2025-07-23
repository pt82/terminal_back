<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PermissionsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $permissions=Permission::all();

        return response()->json([
            "success" => true,
            "message" => "List permissions",
            "data" => $permissions
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $permission=new Permission();
        $permission->name=$request->input('name');
        $permission->slug=$request->input('slug');
        $permission->description=$request->input('description');
        $permission->model='App\Models\Permission';

        return response()->json([
            "success" => $permission->save,
           "id" => $permission->id ?? ''
        ]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        $permission=Permission::find($id);

        return response()->json([
            "success" => true,
            "message" => "Permission",
            "data" => $permission
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $permission = Permission::find($id);
        return response()->json([
            "success" => $permission->update($input),
            "id" => $permission->id ?? ''
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);
        return response()->json([
            "success" => $permission->delete(),
            "id" => $permission->id ?? ''
        ]);

    }
}
