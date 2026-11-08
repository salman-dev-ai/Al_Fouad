<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ينشئ جدول التقييمات: تقييم واحد لكل (عميل + منتج) مرتبط بطلب يثبت الشراء.
    // rating من 0 إلى 5، وtags وsize_data بصيغة JSON اختيارية، وstatus يبدأ pending ولا يُنشر قبل الاعتماد.
    // الفريد (user_id + product_id) يمنع التكرار، والفهرس يسرّع قائمة المنتج المعتمدة.
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 100)->nullable();
            $table->text('body');
            $table->json('tags')->nullable();
            $table->json('size_data')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'status', 'created_at']);
        });
    }

    // يحذف الجدول عند التراجع.
    // لا يمس أي جدول آخر.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
