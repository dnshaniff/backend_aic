<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class ReimbursementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'category_name' => $this->category->category_name,
            ] : null,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'file' => $this->file ? Storage::url($this->file) : null,
            'status' => $this->status,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'nik' => $this->employee->nik,
                'full_name' => $this->employee->full_name,
                'position' => $this->employee->position,
            ] : null,
            'approver' => $this->approver ? [
                'id' => $this->approver->id,
                'nik' => $this->approver->nik,
                'full_name' => $this->approver->full_name,
                'position' => $this->approver->position,
            ] : null,
            'submitted_at' => optional($this->submitted_at)->format('d F Y, H:i'),
            'approved_at' => optional($this->approved_at)->format('d F Y, H:i'),
            'rejected_at' => optional($this->rejected_at)->format('d F Y, H:i'),
            'created_at' => $this->created_at->format('d F Y, H:i'),
            'updated_at' => $this->updated_at->format('d F Y, H:i'),
            'deleted_at' => optional($this->deleted_at)?->format('d F Y, H:i'),
        ];
    }
}
