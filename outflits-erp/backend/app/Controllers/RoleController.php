<?php

namespace App\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController
{
    public function index(Request $request)
    {
        return Role::query()->with('permissions')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        return Role::query()->create($request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ]));
    }

    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate(['permission_ids' => ['required', 'array'], 'permission_ids.*' => ['exists:permissions,id']]);
        $role->permissions()->sync($data['permission_ids']);
        return response()->json($role->load('permissions'));
    }

    public function assignRoleToUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        $user->roles()->syncWithoutDetaching([$data['role_id']]);

        return response()->json($user->load('roles.permissions'));
    }

    public function permissions(Request $request)
    {
        return Permission::query()->paginate($request->integer('per_page', 50));
    }
}
