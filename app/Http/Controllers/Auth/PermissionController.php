<?php

namespace App\Http\Controllers\Auth;

use Throwable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\Auth\PermissionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $cacheKey = "users:{$user->username}:search={$search}:page={$page}:perPage={$perPage}";

            $permissions = Cache::tags('permissions')->remember($cacheKey, now()->addMinutes(5), function () use ($search, $perPage) {
                $query = Permission::query();

                if ($search) {
                    $query->whereRaw('name ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('display_name ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('group_name ILIKE ?', ["%{$search}%"]);
                }

                return $query->latest()->paginate($perPage);
            });

            return PermissionResource::collection($permissions);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve permissions', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'display_name' => 'required|string',
                'name' => 'required|string|unique:permissions,name',
                'group_name' => 'required|string',
            ]);

            $permission = Permission::create($data);
            DB::commit();

            Cache::tags('permissions')->flush();

            return response()->json(['message' => 'Permission created successfully', 'data' => new PermissionResource($permission)], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to retrieve permission', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $permission = Permission::findOrFail($id);

            return response()->json(['data' => new PermissionResource($permission)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Permission not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve permission', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $permission = Permission::findOrFail($id);

            $data = $request->validate([
                'display_name' => 'required|string',
                'name' => 'required|string|unique:permissions,name,' . $permission->id,
                'group_name' => 'required|string',
            ]);

            $permission->update($data);
            DB::commit();

            Cache::tags('permissions')->flush();

            return response()->json(['message' => 'Permission updated successfully', 'data' => new PermissionResource($permission)], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Permission not found'], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update permission', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $permission = Permission::findOrFail($id);

            $permission->delete();
            DB::commit();

            Cache::tags('permissions')->flush();

            return response()->json(['message' => 'Permission deleted successfully'], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete permission', 'error' => $e->getMessage()], 500);
        }
    }
}
