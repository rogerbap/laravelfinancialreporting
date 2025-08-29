<?php
// app/Policies/ReportPolicy.php

namespace App\Policies;

use App\Models\{User, Report};
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view reports');
    }

    public function view(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('view reports') || 
               $user->id === $report->created_by ||
               ($report->status === 'published' && $user->hasPermissionTo('view published reports'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create reports');
    }

    public function update(User $user, Report $report): bool
    {
        return ($user->id === $report->created_by && $report->status === 'draft') ||
               $user->hasPermissionTo('edit reports');
    }

    public function delete(User $user, Report $report): bool
    {
        return ($user->id === $report->created_by && $report->status === 'draft') ||
               $user->hasPermissionTo('delete reports');
    }

    public function publish(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('publish reports');
    }
}