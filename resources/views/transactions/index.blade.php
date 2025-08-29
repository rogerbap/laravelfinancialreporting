{{-- resources/views/transactions/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Transactions</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-secondary" onclick="toggleFilters()">
                <i class="fas fa-filter me-1"></i>Filters
            </button>
            <a href="{{ route('import-export.export') }}?{{ http_build_query(request()->query()) }}" 
               class="btn btn-outline-primary">
                <i class="fas fa-download me-1"></i>Export
            </a>
        </div>
        @can('create', App\Models\Transaction::class)
            <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Transaction
            </a>
        @endcan
    </div>
</div>

<!-- Filters Panel -->
<div id="filtersPanel" class="card mb-4" style="display: none;">
    <div class="card-body">
        <form method="GET" action="{{ route('transactions.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <a href="{{ route('transactions.show', $transaction) }}" 
                                   class="text-decoration-none">
                                    {{ $transaction->reference_number }}
                                </a>
                            </td>
                            <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                            <td>{{ Str::limit($transaction->description, 40) }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $transaction->category->color }}">
                                    {{ $transaction->category->name }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $transaction->type === 'income' ? 'success' : ($transaction->type === 'expense' ? 'danger' : 'info') }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="{{ $transaction->type === 'income' ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $transaction->status === 'approved' ? 'success' : ($transaction->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                            <td>{{ $transaction->creator->name }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('transactions.show', $transaction) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('update', $transaction)
                                        <a href="{{ route('transactions.edit', $transaction) }}" 
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('approve', $transaction)
                                        @if($transaction->isPending())
                                            <form method="POST" action="{{ route('transactions.approve', $transaction) }}" 
                                                  style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success btn-sm"
                                                        onclick="return confirm('Approve this transaction?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No transactions found</p>
                                @can('create', App\Models\Transaction::class)
                                    <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                                        Create First Transaction
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} 
                of {{ $transactions->total() }} transactions
            </div>
            {{ $transactions->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// Show filters if any filter is applied
@if(request()->hasAny(['category_id', 'type', 'status', 'date_from', 'date_to']))
    document.getElementById('filtersPanel').style.display = 'block';
@endif
</script>
@endpush
@endsection