<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // صور التقييم: مسار الملف المعالَج وحجمه بالبايت وترتيبه.
    // الحذف المتتالي يزيل السجلات مع التقييم (والمراقب يحذف الملفات نفسها).
    // الفهرس على review_id لجلب صور كل تقييم بسرعة.
    public function up(): void
    {
        Schema::create('review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('size')->default(0);
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    // يحذف الجدول عند التراجع.
    // لا يمس جدول التقييمات.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::dropIfExists('review_images');
    }
};
