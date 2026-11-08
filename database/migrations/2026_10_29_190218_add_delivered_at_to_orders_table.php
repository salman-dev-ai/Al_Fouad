<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // يضيف وقت التسليم الفعلي إلى الطلبات، وهو أساس نافذة التقييم (6 أشهر).
    // العمود اختياري: الطلبات القديمة تعود لتاريخ إنشائها عند الحساب (ترحيل آمن).
    // يُملأ تلقائيًا عند الانتقال إلى «تم التسليم» (انظر AppServiceProvider).
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable();
        });
    }

    // يحذف العمود عند التراجع.
    // لا يؤثر على بقية أعمدة الطلب.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });
    }
};
