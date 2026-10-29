<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // يضيف عمودًا اختياريًا لحد الشحن المجاني (فارغ = لا شحن مجاني).
    // العمود جديد وقابل للفراغ فلا يمس أي بيانات موجودة (ترحيل آمن).
    // يُملأ من لوحة الإدارة بنفس نوع عمود رسوم التوصيل.
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->decimal('free_shipping_threshold', 10, 2)->nullable()->after('delivery_fee');
        });
    }

    // يحذف العمود عند التراجع.
    // لا يؤثر على بقية الإعدادات.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('free_shipping_threshold');
        });
    }
};
