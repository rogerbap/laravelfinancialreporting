<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->date('transaction_date');
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('report_id')->nullable()->constrained('reports');
            $table->foreignId('created_by')->constrained('users');
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->string('receipt_path')->nullable(); // File upload path
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['type', 'status', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
            $table->index(['reference_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};