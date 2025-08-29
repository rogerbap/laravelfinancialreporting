<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\AuditObserver;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'description', 'amount', 'type', 
        'transaction_date', 'category_id', 'report_id', 'created_by',
        'metadata', 'receipt_path', 'status', 'approved_at', 'approved_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'metadata' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::observe(AuditObserver::class);
        
        static::creating(function ($transaction) {
            if (!$transaction->reference_number) {
                $transaction->reference_number = static::generateReferenceNumber();
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper Methods
    public static function generateReferenceNumber()
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');
        $sequence = str_pad(static::whereDate('created_at', now())->count() + 1, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $sequence;
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}