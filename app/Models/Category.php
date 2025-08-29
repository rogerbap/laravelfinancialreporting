<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\AuditObserver;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'color', 'is_active', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors & Mutators
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    // Helper Methods
    public function getTotalTransactions($dateFrom = null, $dateTo = null)
    {
        $query = $this->transactions();
        
        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }
        
        return $query->sum('amount');
    }
}