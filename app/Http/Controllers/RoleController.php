<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommonResource;
use Illuminate\Http\Request;

use App\Models\Module;
use Spatie\Permission\Models\Role;
use App\Http\Resources\RoleResource;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request){
        $roles = Role::latest();

        if ($request->official)
            $roles = $roles->whereNotIn('name', ['Mentor', 'Assessor', 'RTO']);

        if ($request->search)
            $roles = $roles->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            });

        if ($request->get('rows')) {
            $roles = $roles->paginate($request->get('rows'));
        } else {
            $roles = $roles->get();
        }

        return CommonResource::collection($roles);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        try {
            $role = Role::create([
                'name' => $request->input('name'),
                'description' => $request->input('description')
            ]);
        } catch (\Throwable $th) {
            return message($th->getMessage(), 400);
        }

        return message('Role created successfully', 200, $role);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return RoleResource
     */
    public function show(Role $role)
    {
        $role->load('permissions');

        return RoleResource::make($role);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        try {
            $role = Role::where('id', $id)->update([
                'name' => $request->input('name'),
                'description' => $request->input('description')
            ]);
        } catch (\Throwable $th) {
            return message($th->getMessage(), 400);
        }

        return message('Role updated successfully', 200, $role);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role)
    {
        if ($role->name == 'Admin' || $role->name == 'LE' || $role->name == 'Official' || $role->name == 'DLC')
            return message("You can't delete this role", 200);

        if ($role->delete())
            return message('Role archived successfully', 200);

        return message('Something went wrong', 400);
    }


    public function getPermission()
    {
        $modules = Module::with('permissions')->get();

        return response()->json($modules);
    }


    public function updatePermission(Request $request, Role $role)
    {
        if ($request->permissionIds) {
            $role->syncPermissions($request->permissionIds);
            return message('Permission Updated Successfully', 200);
        }
        return message('something wrong', 403);
    }

    public function getRoleName(Request $request)
    {
        $res = '';
        if (isset($request->id)) {
            $data = DB::table('roles')->where(['id' => $request->id])->first();

            if ($data) {
                $res = $data->name;
            }
        }
        echo $res;
    }
}
