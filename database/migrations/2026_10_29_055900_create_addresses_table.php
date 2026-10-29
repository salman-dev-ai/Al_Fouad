<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// ينشئ جدول عناوين الشحن: كل عنوان يتبع مستخدمًا ويُحذف معه.
// فهرس (المستخدم، الافتراضي) يسرّع جلب العنوان الافتراضي وقائمة العناوين.
// رابط الخريطة اختياري ويُتحقق منه في الخادم أنه رابط خرائط Google فقط.
public function up(): void
{
    Schema::create('addresses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('label', 50)->nullable();
        $table->string('recipient_name', 100);
        $table->string('phone', 20);
        $table->string('city', 100);
        $table->string('address_line', 500);
        $table->string('notes', 500)->nullable();
        $table->string('location_url', 2048)->nullable();
        $table->boolean('is_default')->default(false);
        $table->timestamps();

        $table->index(['user_id', 'is_default']);
    });
}

public function down(): void
{
    Schema::dropIfExists('addresses');
}
};
