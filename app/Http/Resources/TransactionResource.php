<?php
// app/Http/Resources/TransactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'amount' => $this->amount,
            'type' => $this->type,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'metadata' => $this->metadata,
            'receipt_url' => $this->receipt_path ? asset('storage/' . $this->receipt_path) : null,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
        ];
    }
}