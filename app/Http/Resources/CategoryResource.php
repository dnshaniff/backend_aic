<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'category_name' => $this->category_name,
            'limit_per_month' => $this->limit_per_month,
            'created_at' => $this->created_at->format('d F Y, H:i'),
            'updated_at' => $this->updated_at->format('d F Y, H:i'),
            'deleted_at' => optional($this->deleted_at)?->format('d F Y, H:i'),
        ];
    }
}
