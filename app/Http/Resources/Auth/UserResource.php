<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'nik' => $this->employee->nik,
                'email' => $this->employee->email,
                'full_name' => $this->employee->full_name,
                'position' => $this->employee->position
            ] : null,
            'role' => $this->roles->first()?->name,
            'username' => $this->username,
            'status' => $this->status,
            'created_at' => $this->created_at->format('d F Y, H:i'),
            'updated_at' => $this->updated_at->format('d F Y, H:i'),
            'deleted_at' => optional($this->deleted_at)?->format('d F Y, H:i'),
        ];
    }
}
