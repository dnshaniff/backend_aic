<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ReimbursementResource;
use App\Jobs\NotifyReimbursement;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReimbursementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $user->username === 'administrator';

            $search = $request->input('search');
            $categoryFilter = $request->input('category');
            $statusFilter = $request->input('status');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $cacheKey = "users:{$user->username}:search={$search}:category={$categoryFilter}:status={$statusFilter}:page={$page}:perPage={$perPage}";

            $reimbursements = Cache::tags('reimbursements')->remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $search, $categoryFilter, $statusFilter, $perPage) {
                $query = Reimbursement::query()
                    ->with(['category', 'employee'])
                    ->when($isAdmin, fn($q) => $q->withTrashed());

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'ILIKE', "%{$search}%")
                            ->orWhereHas('employee', function ($q2) use ($search) {
                                $q2->where('full_name', 'ILIKE', "%{$search}%");
                            });
                    });
                }

                if ($categoryFilter) {
                    $query->whereHas('category', function ($q) use ($categoryFilter) {
                        $q->where('category_name', 'ILIKE', "%{$categoryFilter}%");
                    });
                }

                if ($statusFilter) {
                    $query->where('status', $statusFilter);
                }

                return $query->latest()->paginate($perPage);
            });

            return ReimbursementResource::collection($reimbursements);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve reimbursements', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->username === 'administrator';
        $employee = $isAdmin ? null : $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Unauthorized: Employee not found'], 403);
        }

        DB::beginTransaction();

        try {
            $data = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string',
                'description' => 'nullable|string',
                'amount' => 'required|integer|min:1000|max:100000000',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            $category = Category::findOrFail($data['category_id']);
            $yearMonth = now()->format('Ym');
            $nik = $employee->nik;

            $reimbursementCount = Reimbursement::where('category_id', $category->id)
                ->where('created_by', $employee->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count() + 1;

            $reimbursementNumber = strtoupper($category->category_name) . '-' . $yearMonth . '-' .
                str_pad($reimbursementCount, 2, '0', STR_PAD_LEFT) . '_' . $nik;

            $storedFile = $request->file('file')->storeAs('reimbursements', $reimbursementNumber . '.' . $request->file('file')->getClientOriginalExtension(), 'public');

            $data['file'] = $storedFile;
            $data['created_by'] = $employee->id;

            $reimbursement = Reimbursement::create($data);
            $reimbursement->load(['category', 'employee']);

            DB::commit();

            Cache::tags('reimbursements')->flush();

            return response()->json(['message' => 'Reimbursement created successfully', 'data' => new ReimbursementResource($reimbursement)], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            if ($storedFile && Storage::disk('public')->exists($storedFile)) {
                Storage::disk('public')->delete($storedFile);
            }
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            if ($storedFile && Storage::disk('public')->exists($storedFile)) {
                Storage::disk('public')->delete($storedFile);
            }
            return response()->json(['message' => 'Failed to create reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $reimbursement = Reimbursement::findOrFail($id);

            return response()->json(new ReimbursementResource($reimbursement));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Reimbursement not found'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $isAdmin = $user->username === 'administrator';
        $employee = $isAdmin ? null : $user->employee;

        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->status !== 'draft' && ! $isAdmin) {
                return response()->json(['message' => 'Only draft reimbursements can be updated'], 403);
            }

            if (! $isAdmin && $reimbursement->created_by !== $employee->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string',
                'description' => 'nullable|string',
                'amount' => 'required|integer|min:1000|max:100000000',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($data['category_id'] !== $reimbursement->category_id) {
                $category = Category::findOrFail($data['category_id']);
                $yearMonth = now()->format('Ym');
                $nik = $employee->nik;

                $count = Reimbursement::where('category_id', $category->id)
                    ->where('created_by', $employee->id)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();

                $reimbursementNumber = strtoupper($category->category_name) . '-' . $yearMonth . '-' .
                    str_pad($count + 1, 2, '0', STR_PAD_LEFT) . '_' . $nik;

                $data['reimbursement_number'] = $reimbursementNumber;
            } else {
                $reimbursementNumber = $reimbursement->reimbursement_number;
            }

            if ($request->hasFile('file')) {
                if ($reimbursement->file && Storage::disk('public')->exists($reimbursement->file)) {
                    Storage::disk('public')->delete($reimbursement->file);
                }

                $data['file'] = $request->file('file')->storeAs(
                    'reimbursements',
                    $reimbursementNumber . '.' . $request->file('file')->getClientOriginalExtension(),
                    'public'
                );
            }

            $reimbursement->update($data);
            $reimbursement->load(['category', 'employee']);

            DB::commit();

            Cache::tags('reimbursements')->flush();

            return response()->json([
                'message' => 'Reimbursement updated successfully',
                'data' => new ReimbursementResource($reimbursement),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reimbursement not found'], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        $isAdmin = $user->username === 'administrator';
        $employee = $isAdmin ? null : $user->employee;

        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->trashed()) {
                return response()->json(['message' => 'Reimbursement already deleted'], 400);
            }

            if ($reimbursement->status !== 'draft' && ! $isAdmin) {
                return response()->json(['message' => 'Only draft reimbursements can be deleted'], 403);
            }

            if (! $isAdmin && $reimbursement->created_by !== $employee->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $reimbursement->delete();

            DB::commit();

            Cache::tags('reimbursements')->flush();

            return response()->json(['message' => 'Reimbursement deleted successfully'], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function restore(string $id)
    {
        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::withTrashed()->findOrFail($id);

            if (! $reimbursement->trashed()) {
                return response()->json(['message' => 'Reimbursement is not deleted'], 400);
            }

            $reimbursement->restore();

            DB::commit();

            Cache::tags('reimbursements')->flush();

            return response()->json(['message' => 'Reimbursement restored successfully']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reimbursement not found'], 404);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to restore reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function force(string $id)
    {
        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::withTrashed()->findOrFail($id);

            if (! $reimbursement->trashed()) {
                return response()->json(['message' => 'Reimbursement must be soft-deleted first'], 400);
            }

            $filePath = $reimbursement->file;

            $reimbursement->forceDelete();

            DB::commit();

            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            Cache::tags('reimbursements')->flush();

            return response()->json(['message' => 'Reimbursement permanently deleted successfully']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reimbursement not found'], 404);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to permanently delete reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function submit(string $id)
    {
        $user = auth()->user();
        $isAdmin = $user->username === 'administrator';
        $employee = $isAdmin ? null : $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Unauthorized: Employee not found'], 403);
        }

        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->status !== 'draft') {
                return response()->json(['message' => 'Only draft reimbursements can be submitted'], 400);
            }

            if ($reimbursement->created_by !== $employee->id) {
                return response()->json(['message' => 'Unauthorized to submit this reimbursement'], 403);
            }

            $category = $reimbursement->category;

            $currentMonthTotal = Reimbursement::where('created_by', $employee->id)
                ->where('category_id', $category->id)
                ->whereIn('status', ['pending', 'approved'])
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('amount');
            $combinedTotal = $currentMonthTotal + $reimbursement->amount;

            if ($combinedTotal > $category->limit_per_month) {
                return response()->json([
                    'message' => 'Reimbursement exceeds monthly limit for this category',
                    'limit_per_month' => $category->limit_per_month,
                    'current_total' => $currentMonthTotal,
                    'attempted_total' => $combinedTotal
                ], 422);
            }

            $reimbursement->update([
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            DB::commit();

            dispatch(new NotifyReimbursement($reimbursement));

            Cache::tags('reimbursements')->flush();

            return response()->json([
                'message' => 'Reimbursement submitted successfully',
                'data' => new ReimbursementResource($reimbursement->fresh(['category', 'employee']))
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reimbursement not found'], 404);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit reimbursement', 'error' => $e->getMessage()], 500);
        }
    }

    public function approval(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Unauthorized: Employee not found'], 403);
        }

        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->status !== 'pending') {
                return response()->json(['message' => 'Only pending reimbursements can be approved/rejected'], 400);
            }

            $updateData = [
                'status' => $request->status,
                'approved_by' => $employee->id,
            ];

            if ($request->status === 'approved') {
                $updateData['approved_at'] = now();
            } else {
                $updateData['rejected_at'] = now();
            }

            $reimbursement->update($updateData);
            $reimbursement->load(['employee', 'category', 'approver']);

            DB::commit();
            Cache::tags('reimbursements')->flush();

            return response()->json(['message' => 'Reimbursement ' . $request->status . ' successfully', 'data' => new ReimbursementResource($reimbursement)], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reimbursement not found'], 404);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update status', 'error' => $e->getMessage()], 500);
        }
    }
}
