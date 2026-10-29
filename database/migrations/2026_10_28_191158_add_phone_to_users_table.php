<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// يضيف رقم الجوال (فريدًا) إلى المستخدمين ليسجّل به العميل دخوله.
// يجعل البريد اختياريًا لأن عملاء المتجر قد لا يملكون بريدًا (والمدير يبقى ببريده).
// الفهرس الفريد على البريد يبقى كما هو، وقيم NULL المتعددة مسموحة فيه.
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone', 20)->nullable()->unique()->after('email');
        $table->string('email')->nullable()->change();
    });
}

// يزيل عمود الجوال، ويعيد البريد إلزاميًا فقط إن لم يوجد مستخدمون بلا بريد.
// وإلا يبقى اختياريًا حتى لا يفشل الرجوع ولا تضيع بيانات.
public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropUnique(['phone']);
        $table->dropColumn('phone');
    });

    if (DB::table('users')->whereNull('email')->doesntExist()) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
}
};
