<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ينشئ جدول طلبات «أبلغني عند التوفر»، سجل لكل (خيار + رقم هاتف).
    // consent_at دليل الموافقة، وavailable_at يُملأ حين يعود المخزون، وnotified_at حين يُبلَّغ العميل.
    // الفريد (الخيار + الرقم) يمنع التكرار، والفهرس يسرّع قائمة «المتوفر الآن» في الإدارة.
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->timestamp('consent_at');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'phone']);
            $table->index(['available_at', 'notified_at']);
        });
    }

    // يحذف الجدول عند التراجع.
    // لا يمس أي جدول آخر.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
