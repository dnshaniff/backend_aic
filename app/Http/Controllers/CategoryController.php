<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\CategoryResource;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
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

            $categories = Cache::tags('categories')->remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $search, $perPage) {
                $query = Category::query()->when($isAdmin, fn($q) => $q->withTrashed());

                if ($search) {
                    $query->whereRaw('category_name ILIKE ?', ["%{$search}%"]);
                }

                return $query->latest()->paginate($perPage);
            });

            return CategoryResource::collection($categories);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve categories', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'category_name' => 'required|string|unique:categories,category_name',
                'limit_per_month' => 'required|integer',
            ]);

            $category = Category::create($data);

            Cache::tags('categories')->flush();

            return response()->json(['message' => 'Category created successfully', 'data' => new CategoryResource($category)], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to create category', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $category = Category::findOrFail($id);

            return response()->json(new CategoryResource($category));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve category', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $category = Category::findOrFail($id);

            $data = $request->validate([
                'category_name' => 'required|string|unique:categories,category_name,' . $category->id,
                'limit_per_month' => 'required|integer',
            ]);

            $category->update($data);

            Cache::tags('categories')->flush();

            return response()->json(['message' => 'Category updated successfully', 'data' => new CategoryResource($category)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to update category', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $category = Category::findOrFail($id);

            if ($category->trashed()) {
                return response()->json(['message' => 'Category already deleted'], 400);
            }

            $category->delete();

            Cache::tags('categories')->flush();

            return response()->json(['message' => 'Category deleted successfully'], 200);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to delete category', 'error' => $e->getMessage()], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);

            if (! $category->trashed()) {
                return response()->json(['message' => 'Category is not deleted'], 400);
            }

            $category->restore();

            Cache::tags('categories')->flush();

            return response()->json(['message' => 'Category restored successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to restore category', 'error' => $e->getMessage()], 500);
        }
    }

    public function force(string $id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);

            if (! $category->trashed()) {
                return response()->json(['message' => 'Category must be soft-deleted first'], 400);
            }

            $category->forceDelete();

            Cache::tags('categories')->flush();

            return response()->json(['message' => 'Category permanently deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to permanently delete category', 'error' => $e->getMessage()], 500);
        }
    }
}
