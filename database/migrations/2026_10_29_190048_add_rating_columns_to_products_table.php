<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // يضيف متوسط التقييم وعدده إلى المنتجات (مخزَّنان لتفادي حسابهما عند كل عرض بطاقة).
    // أعمدة جديدة بقيم افتراضية صفر فلا تمس بيانات المنتجات الحالية (ترحيل آمن).
    // تُحدَّث تلقائيًا من مراقب التقييمات.
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
        });
    }

    // يحذف العمودين عند التراجع.
    // لا يؤثر على بقية أعمدة المنتج.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });
    }
};
