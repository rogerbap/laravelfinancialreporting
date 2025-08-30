<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Transaction, Category, User};
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $categories = Category::all();

        // Create 50 sample transactions
        for ($i = 1; $i <= 50; $i++) {
            Transaction::create([
                'reference_number' => 'TXN' . date('Ymd') . sprintf('%03d', $i),
                'description' => 'Sample transaction ' . $i,
                'amount' => rand(50, 2000),
                'type' => rand(0, 1) ? 'income' : 'expense',
                'transaction_date' => Carbon::now()->subDays(rand(0, 180)),
                'category_id' => $categories->random()->id,
                'created_by' => $user->id,
                'status' => 'approved',
            ]);
        }
    }
}