<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // جدول إعدادات عام (مفتاح/قيمة) تتحكم به الإدارة، ويُستعمل الآن لإعدادات صور التقييمات.
    // المفتاح فريد، والقيمة نص يُحوَّل عند القراءة.
    // تُخزَّن الإعدادات في الكاش ويُمسح عند أي تعديل.
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    // يحذف الجدول عند التراجع.
    // لا يمس أي جدول آخر.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
