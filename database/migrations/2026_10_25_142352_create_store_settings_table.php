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
    
    Schema::create('store_settings', function (Blueprint $table) {
        $table->id();
        $table->string('phone', 30)->nullable();
        $table->string('whatsapp_phone', 30)->nullable();
        $table->string('email')->nullable();
        $table->text('address')->nullable();
        $table->string('map_url', 2048)->nullable();
        $table->decimal('delivery_fee', 10, 2)->default(0);
        $table->boolean('tax_enabled')->default(false);
        $table->decimal('tax_rate', 5, 2)->default(0);
        $table->string('currency_code', 3);
        $table->json('opening_hours')->nullable();
        $table->json('social_links')->nullable();
        $table->string('announcement_text')->nullable();
        $table->boolean('announcement_enabled')->default(false);
        $table->boolean('maintenance_mode')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
