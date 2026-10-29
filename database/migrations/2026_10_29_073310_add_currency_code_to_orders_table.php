<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
// يحفظ عملة كل طلب وقت الشراء، فلا يتأثر عرض الطلبات القديمة إن غُيّرت عملة المحل لاحقًا.
// الطلبات الموجودة تُملأ بعملة المحل الحالية (أو YER إن لم تُضبط) في الترحيل نفسه.
// العمود قابل للإفراغ احتياطًا، والعرض يرجع لعملة المحل عند غيابه.
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('currency_code', 3)->nullable()->after('total');
    });

    DB::table('orders')->update([
        'currency_code' => DB::table('store_settings')->value('currency_code') ?? 'YER',
    ]);
}

public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('currency_code');
    });
}
};
