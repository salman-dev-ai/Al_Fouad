<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  // جدول ربط بين المستخدم والمنتج المفضّل، والمفتاح المركّب يمنع تكرار المنتج نفسه للمستخدم نفسه.
// يُحذف السطر تلقائيًا بحذف المستخدم أو المنتج.
// تاريخ الإضافة يُحفظ لعرض الأحدث أولًا.
public function up(): void
{
    Schema::create('favorites', function (Blueprint $table) {
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->timestamps();

        $table->primary(['user_id', 'product_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('favorites');
}
};
