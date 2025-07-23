<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Rolelabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolesController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function index()
    {
        if (Auth::user()->root_access) {
            $roles = Role::select('id as role_id', 'name', 'id')->withLabels()->get();
            return response()->json([
                "success" => true,
                "message" => "List Roles",
                "data" => $roles ?? []
            ]);
        }
        if (Auth::user()->level()) {
            $roles = Role::select('id as role_id', 'name', 'id')->withLabels()->Where('level', '<=', Auth::user()->level())->get();
        }
            return response()->json([
                "success" => true,
                "message" => "List Roles",
                "data" => $roles ?? []
            ]);

    }

    /**
    * @param  int  $id
    * @return JsonResponse
    */
    public function show($id)
    {
        $result=[];
       $roles=DB::table('permission_role')
        ->join('roles', 'roles.id','=','permission_role.role_id')
        ->join('permissions', 'permissions.id','=','permission_role.permission_id')
        ->select('roles.id as role_id','roles.name','roles.slug','roles.description','roles.level', 'permissions.name as permission')
        ->where('permission_role.role_id','=',$id)->get();
        $result=[
            'role_id'=>$roles[0]->id,
            'name'=>$roles[0]->name,
            'slug'=>$roles[0]->slug,
            'description'=>$roles[0]->description,
            'level'=>$roles[0]->level
        ];
       foreach ($roles as $role){
           $result['permission'][]=[
               $role->permission];
       }
        return response()->json([
            'success' => true,
            "message" => "Role",
            'data' => $result
        ],200);
    }

    public function store(Request $request)
    {
        $permission=[];
        $role=new Role();
        $role->name=$request->name;
        $role->slug=$request->slug;
        $role->slug=$request->slug;
        $role->description=$request->description;
        $role->level=$request->level;
        $role->save();

        foreach ($request->permissions as $permission){
           array_push($permission,$permission['id']);
        }
        if(!empty($permission)) {
            $role->syncPermissions([$permission]);
        }

        return response()->json([
            'success' => true,
            'id' => $role->id ?? ''
        ]);
    }

    public function update(Request $request, $id)
    {
        $input=$request->all();
        $role=Role::find($id);
        $role->update($input);
        $permission=[];
        if(isset($request->permissions)) {
            foreach ($request->permissions as $permission){
                array_push($permission, $permission['id']);
            }
        }
        if(!empty($permission)){
            $role->attachPermission($permission);
        }
        elseif(empty($permission)){$role->detachAllPermissions();}

        return response()->json([
            'success' => true,
            'id' => $role->id ?? ''
        ]);
    }

    public function destroy($id)
    {
        $role=Role::find($id);
        $role->delete();
        $role->detachAllPermissions();

        return response()->json([
            'success' => true,
            'id' => $role->id ?? ''
        ]);
    }

    public function labelIndex()
    {
        if (auth()->user()->level() < 100)
            abort(403);

        return Role::withLabels(auth()->user()->franchises()->pluck('id')->toArray())
            ->get();
    }

    public function labelStore(Request $request)
    {
        if (auth()->user()->level() < 100)
            abort(403);

        $label = Rolelabel::create(['name' => $request->name ?? 'noname']);
        $this->labelStoreUpdate($request->franchises ?? [], $request->role_id ?? 0, $label);
        return ['success' => true];
    }

    public function labelUpdate(Request $request)
    {
        if (auth()->user()->level() < 100)
            abort(403);

        $label = Rolelabel::find($request->id ?? 0);
        if (!$label)
            abort(404);

        $this->labelAccess($label);

        $label->name = $request->name ?? 'noname';
        $label->save();
        $this->labelStoreUpdate($request->franchises ?? [], $request->role_id ?? 0, $label);
        return ['success' => true];
    }

    protected function labelStoreUpdate($franchises, $roleId, $label)
    {
        $toPivot = [];
        foreach ($franchises as $franchise) {
            $toPivot[$franchise] = ['role_id' => $roleId];
        }
        $label->franchises()->sync($toPivot);
    }

    protected function labelAccess($label)
    {
        $userFranchises = auth()->user()->franchises();
        if ($userFranchises->diff($label->franchises)->count() === $userFranchises->count())
            abort(403);
    }

    public function labelDestroy($id)
    {
        if (auth()->user()->level() < 100)
            abort(403);

        $label = Rolelabel::find($id);
        if (!$label)
            abort(404);

        $this->labelAccess($label);

        $label->franchises()->sync([]);
        $label->delete();
        return ['success' => true];
    }

    public function labelShow($id)
    {
        if (auth()->user()->level() < 100)
            abort(403);

        $label = Rolelabel::find($id);
        if (!$label)
            abort(404);

        $this->labelAccess($label);

        return [
            'success' => true,
            'data' => $label
        ];
    }
}

