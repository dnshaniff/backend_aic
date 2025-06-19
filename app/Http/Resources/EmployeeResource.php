<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'nik' => $this->nik,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'position' => $this->position,
            'user' => $this->user ? [
                'username' => $this->user->username,
                'status' => $this->user->status
            ] : null,
            'created_at' => $this->created_at->format('d F Y, H:i'),
            'updated_at' => $this->updated_at->format('d F Y, H:i'),
            'deleted_at' => optional($this->deleted_at)?->format('d F Y, H:i'),
        ];
    }
}
