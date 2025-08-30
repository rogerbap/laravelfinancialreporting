<?php
// app/Policies/TransactionPolicy.php

namespace App\Policies;

use App\Models\{User, Transaction};
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view transactions');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->hasPermissionTo('view transactions') || 
               $user->id === $transaction->created_by;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create transactions');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        // Can edit own transactions if pending, or has edit permission
        return ($user->id === $transaction->created_by && $transaction->isPending()) ||
               $user->hasPermissionTo('edit transactions');
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        // Can delete own transactions if pending, or has delete permission
        return ($user->id === $transaction->created_by && $transaction->isPending()) ||
               $user->hasPermissionTo('delete transactions');
    }

    public function approve(User $user, Transaction $transaction): bool
    {
        // Cannot approve own transactions
        return $user->hasPermissionTo('approve transactions') && 
               $user->id !== $transaction->created_by;
    }

    public function import(User $user): bool
    {
        return $user->hasPermissionTo('import transactions');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export transactions');
    }
}