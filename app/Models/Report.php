<?php
// app/Models/Report.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\AuditObserver;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'type', 'status', 'period_start', 
        'period_end', 'filters', 'total_amount', 'transaction_count', 
        'created_by', 'published_at'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'filters' => 'array',
        'total_amount' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::observe(AuditObserver::class);
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPeriod($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end])
                    ->orWhereBetween('period_end', [$start, $end]);
    }

    // Helper Methods
    public function generateSummary()
    {
        $transactions = $this->getTransactionsForPeriod();
        
        return [
            'total_income' => $transactions->where('type', 'income')->sum('amount'),
            'total_expenses' => $transactions->where('type', 'expense')->sum('amount'),
            'transaction_count' => $transactions->count(),
            'categories' => $transactions->groupBy('category.name')->map->sum('amount'),
        ];
    }

    private function getTransactionsForPeriod()
    {
        return Transaction::with('category')
            ->whereBetween('transaction_date', [$this->period_start, $this->period_end])
            ->when($this->filters, function ($query) {
                if (isset($this->filters['categories'])) {
                    $query->whereIn('category_id', $this->filters['categories']);
                }
                if (isset($this->filters['types'])) {
                    $query->whereIn('type', $this->filters['types']);
                }
            })
            ->get();
    }
}