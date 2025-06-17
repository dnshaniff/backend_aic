<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->whereRaw('username ILIKE ?', ["%{$search}%"]);
        }

        $users = $query->latest()->paginate($request->input('per_page', 10));

        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'nullable|uuid',
            'username' => 'required|string|unique:users,username',
            'status' => 'nullable|in:active,inactive'
        ]);

        $password = Str::random(12);
        $data['password'] = Hash::make($password);
        $data['status'] = $data['status'] ?? 'active';

        $user = User::create($data);

        return response()->json(['message' => 'User created successfully', 'data' => new UserResource($user)], 201);
    }
}
