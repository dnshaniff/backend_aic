<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\EmployeeResource;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeController extends Controller
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

            $employees = Cache::tags('employees')->remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $search, $perPage) {
                $query = Employee::query()->when($isAdmin, fn($q) => $q->withTrashed());

                if ($search) {
                    $query->whereRaw('nik ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('full_name ?', ["%{$search}%"])
                        ->orWhereRaw('position ?', ["%{$search}%"]);
                }

                return $query->latest()->paginate($perPage);
            });

            return EmployeeResource::collection($employees);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve employees', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'nik' => 'required|string|unique:employees,nik',
                'full_name' => 'required|string',
                'position' => 'required|string'
            ]);

            $employee = Employee::create($data);

            Cache::tags('employees')->flush();

            return response()->json(['message' => 'Employee created successfully', 'data' => new EmployeeResource($employee)], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to create employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            return response()->json(new EmployeeResource($employee));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Employee not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $data = $request->validate([
                'nik' => 'required|string|unique:employees,nik,' . $employee->id,
                'full_name' => 'required|string',
                'position' => 'required|string'
            ]);

            $nikChanged = $data['nik'] !== $employee->nik;

            $employee->update($data);

            if ($nikChanged && $employee->user) {
                $employee->user->update([
                    'username' => strtolower(str_replace(' ', '', $data['nik'])),
                ]);
            }

            Cache::tags('employees')->flush();

            return response()->json(['message' => 'Employee updated successfully', 'data' => new EmployeeResource($employee)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Employee not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to update employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            if ($employee->trashed()) {
                return response()->json(['message' => 'Employee already deleted'], 400);
            }

            $employee->delete();

            Cache::tags('employees')->flush();

            return response()->json(['message' => 'Employee deleted successfully'], 200);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to delete employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);

            if (! $employee->trashed()) {
                return response()->json(['message' => 'Employee is not deleted'], 400);
            }

            $employee->restore();

            Cache::tags('employees')->flush();

            return response()->json(['message' => 'Employee restored successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Employee not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to restore employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function force(string $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);

            if (! $employee->trashed()) {
                return response()->json(['message' => 'must be soft-deleted first'], 400);
            }

            $employee->forceDelete();

            Cache::tags('employees')->flush();

            return response()->json(['message' => 'Employee permanently deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Employee not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to permanently delete employee', 'error' => $e->getMessage()], 500);
        }
    }
}
