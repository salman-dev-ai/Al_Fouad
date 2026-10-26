<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_number', 30)->unique();
        $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
        $table->string('customer_name');
        $table->string('customer_phone', 30);
        $table->text('delivery_address');
        $table->string('city', 100)->nullable();
        $table->text('notes')->nullable();
        $table->decimal('subtotal', 10, 2);
        $table->decimal('tax_rate', 5, 2)->default(0);
        $table->decimal('tax_amount', 10, 2)->default(0);
        $table->decimal('delivery_fee', 10, 2)->default(0);
        $table->decimal('total', 10, 2);
        $table->string('status', 20)->default('pending');
        $table->timestamps();

        $table->index(['status', 'created_at']);
        $table->index('customer_phone');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
