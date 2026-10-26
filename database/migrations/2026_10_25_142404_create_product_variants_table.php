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
    Schema::create('product_variants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->string('size', 20);
        $table->string('color', 50);
        $table->decimal('price', 10, 2);
        $table->decimal('compare_at_price', 10, 2)->nullable();
        $table->unsignedInteger('stock_quantity')->default(0);
        $table->string('sku', 64)->nullable()->unique();
        $table->timestamps();

        $table->unique(['product_id', 'size', 'color']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
