<?php

namespace App\Http\Controllers\Auth;

use Throwable;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\Auth\RoleResource;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $cacheKey = "users:{$user->username}:search={$search}:page={$page}:perPage={$perPage}";

            $roles = Cache::tags('roles')->remember($cacheKey, now()->addMinutes(5), function () use ($search, $perPage) {
                $query = Role::query();

                if ($search) {
                    $query->whereRaw('name ILIKE ?', ["%{$search}%"]);
                }

                return $query->latest()->paginate($perPage);
            });

            return RoleResource::collection($roles);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve roles', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => 'required|string|unique:roles,name',
                'permissions.*' => 'required|exists:permissions,name'
            ]);

            $role = Role::create(['name' => $data['name']]);
            $role->syncPermissions($data['permissions']);
            DB::commit();

            Cache::tags('roles')->flush();

            return response()->json(['message' => 'Role created successfully', 'data' => new RoleResource($role)], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to retrieve role', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $role = Role::findOrFail($id);

            return response()->json(['data' => new RoleResource($role)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Role not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve role', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $role = Role::findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|unique:roles,name,' . $role->id,
                'permissions.*' => 'required|exists:permissions,name'
            ]);

            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions']);
            DB::commit();

            Cache::tags('roles')->flush();

            return response()->json(['message' => 'Role updated successfully', 'data' => new RoleResource($role)], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Role not found'], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update role', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $role = Role::findOrFail($id);

            $role->delete();
            DB::commit();

            Cache::tags('roles')->flush();

            return response()->json(['message' => 'Role deleted successfully'], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete role', 'error' => $e->getMessage()], 500);
        }
    }
}
