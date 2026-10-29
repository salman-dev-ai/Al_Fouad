<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// يربط الطلب بحساب المشتري (اختياريًا، فطلبات الزوار تبقى user_id = NULL).
// nullOnDelete: حذف الحساب لا يحذف الطلب (الطلب سجل مالي يبقى).
// فهرس (المستخدم، التاريخ) يسرّع صفحة «طلباتي».
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        $table->index(['user_id', 'created_at']);
    });
}

public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropIndex(['user_id', 'created_at']);
        $table->dropConstrainedForeignId('user_id');
    });
}
};
