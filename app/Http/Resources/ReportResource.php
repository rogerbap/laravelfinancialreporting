<?php
// app/Http/Resources/ReportResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'period_start' => $this->period_start->format('Y-m-d'),
            'period_end' => $this->period_end->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'transaction_count' => $this->transaction_count,
            'filters' => $this->filters,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'summary' => $this->when($this->summary ?? false, function() {
                return $this->generateSummary();
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}