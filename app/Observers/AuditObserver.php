<?php
// app/Observers/AuditObserver.php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->logActivity($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->logActivity($model, 'updated', $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->logActivity($model, 'deleted', $model->getAttributes(), null);
    }

    private function logActivity(Model $model, string $event, ?array $oldValues, ?array $newValues): void
    {
        // Don't log if user is not authenticated (e.g., during seeding)
        if (!auth()->check()) {
            return;
        }

        // Filter out sensitive attributes
        $sensitiveFields = ['password', 'remember_token'];
        if ($oldValues) {
            $oldValues = collect($oldValues)->except($sensitiveFields)->toArray();
        }
        if ($newValues) {
            $newValues = collect($newValues)->except($sensitiveFields)->toArray();
        }

        AuditLog::create([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}