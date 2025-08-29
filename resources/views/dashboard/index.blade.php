{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Financial Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <select class="form-select" id="periodSelector" onchange="changePeriod()">
                <option value="current_month" {{ $period == 'current_month' ? 'selected' : '' }}>Current Month</option>
                <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="current_quarter" {{ $period == 'current_quarter' ? 'selected' : '' }}>Current Quarter</option>
                <option value="current_year" {{ $period == 'current_year' ? 'selected' : '' }}>Current Year</option>
            </select>
        </div>
        <a href="{{ route('reports.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>New Report
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Income
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ${{ number_format($data['summary']['total_income'], 2) }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-arrow-up fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Total Expenses
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ${{ number_format($data['summary']['total_expenses'], 2) }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-arrow-down fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Net Income
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ${{ number_format($data['summary']['total_income'] - $data['summary']['total_expenses'], 2) }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calculator fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pending Approvals
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $data['summary']['pending_approvals'] }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Category Breakdown Chart -->
    <div class="col-xl-6 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Category Breakdown</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu shadow">
                        <a class="dropdown-item" href="#" onclick="downloadChart('categoryChart', 'category-breakdown')">Download Chart</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:350px">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="col-xl-6 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Monthly Trends</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:350px">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions & Top Categories -->
<div class="row">
    <!-- Recent Transactions -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
                <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['recentTransactions'] as $transaction)
                                <tr onclick="window.location.href='{{ route('transactions.show', $transaction) }}'" 
                                    style="cursor: pointer;">
                                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td>{{ Str::limit($transaction->description, 30) }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $transaction->category->color }}">
                                            {{ $transaction->category->name }}
                                        </span>
                                    </td>
                                    <td class="{{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->type === 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->status === 'approved' ? 'success' : ($transaction->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No recent transactions</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Categories -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Categories</h6>
            </div>
            <div class="card-body">
                @forelse($data['topCategories'] as $category)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle" style="width: 40px; height: 40px; background-color: {{ $category->color }}"></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">{{ $category->name }}</h6>
                            <small class="text-muted">${{ number_format($category->transactions_sum_amount ?? 0, 2) }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">No data available</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Category Breakdown Pie Chart
const categoryData = @json($data['categoryBreakdown']);
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(categoryData),
        datasets: [{
            data: Object.values(categoryData),
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ':  + context.parsed.toLocaleString();
                    }
                }
            }
        }
    }
});

// Monthly Trends Line Chart
const trendsData = @json($data['monthlyTrends']);
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: trendsData.map(item => item.month),
        datasets: [{
            label: 'Income',
            data: trendsData.map(item => item.income),
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }, {
            label: 'Expenses',
            data: trendsData.map(item => item.expenses),
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

function changePeriod() {
    const period = document.getElementById('periodSelector').value;
    window.location.href = `{{ route('dashboard') }}?period=${period}`;
}

function downloadChart(chartId, filename) {
    const canvas = document.getElementById(chartId);
    const url = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.png';
    a.click();
}
</script>
@endpush
@endsection