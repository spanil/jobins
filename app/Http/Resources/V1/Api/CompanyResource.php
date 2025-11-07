<?php

namespace App\Http\Resources\V1\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'import_errors' => $this->import_errors,
            'is_duplicate' => $this->is_duplicate,
            'duplicate_of' => $this->duplicate_of,
            'import_batch' => $this->import_batch,
        ];
    }
}
