<?php

namespace App\Http\Controllers\Auth;

use Throwable;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $user->username === 'administrator';

            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $cacheKey = "users:{$user->username}:search={$search}:page={$page}:perPage={$perPage}";

            $users = Cache::tags('users')->remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $search, $perPage) {
                $query = User::query()->when($isAdmin, fn($q) => $q->withTrashed());

                if ($search) {
                    $query->whereRaw('username ILIKE ?', ["%{$search}%"]);
                }

                return $query->latest()->paginate($perPage);
            });

            return UserResource::collection($users);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve users', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'employee_id' => 'nullable|uuid',
                'username' => 'required|string|unique:users,username',
                'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z]).+$/',
                'password_confirmation' => 'required|same:password',
                'status' => 'required|in:active,inactive'
            ]);

            $data['password'] = Hash::make($data['password']);
            $user = User::create($data);

            Cache::tags('users')->flush();

            return response()->json(['message' => 'User created successfully', 'data' => new UserResource($user)], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json(new UserResource($user));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve user', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $data = $request->validate([
                'employee_id' => 'nullable|uuid',
                'username' => 'required|string|unique:users,username,' . $user->id,
                'password' => 'nullable|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z]).+$/',
                'password_confirmation' => 'required_with:password|same:password',
                'status' => 'required|in:active,inactive'
            ]);

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            Cache::tags('users')->flush();

            return response()->json(['message' => 'User updated successfully', 'data' => new UserResource($user)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to update user', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            if ($user->trashed()) {
                return response()->json(['message' => 'User already deleted'], 400);
            }

            $user->delete();

            Cache::tags('users')->flush();

            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to delete user', 'error' => $e->getMessage()], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            if (! $user->trashed()) {
                return response()->json(['message' => 'User is not deleted'], 400);
            }

            $user->restore();

            Cache::tags('users')->flush();

            return response()->json(['message' => 'User restored successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to restore user', 'error' => $e->getMessage()], 500);
        }
    }

    public function force(string $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            if (! $user->trashed()) {
                return response()->json(['message' => 'User must be soft-deleted first'], 400);
            }

            $user->forceDelete();

            Cache::tags('users')->flush();

            return response()->json(['message' => 'User permanently deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to permanently delete user.', 'error' => $e->getMessage()], 500);
        }
    }
}
