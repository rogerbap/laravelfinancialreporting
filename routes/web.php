<?php

use App\Http\Controllers\{DashboardController, TransactionController, CategoryController, ReportController};

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::resource('transactions', TransactionController::class);
Route::resource('categories', CategoryController::class);
Route::resource('reports', ReportController::class);

// Add missing routes referenced in your view
Route::get('profile/edit', function() { return redirect('/'); })->name('profile.edit');
Route::post('logout', function() { return redirect('/'); })->name('logout');
Route::get('import-export', function() { return view('import-export.import'); })->name('import-export.import');