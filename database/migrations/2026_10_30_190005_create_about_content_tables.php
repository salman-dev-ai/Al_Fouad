<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ينشئ جداول صفحة «قصتنا»: المحتوى المفرد (سجل واحد)، والشركاء، وآراء العملاء، والفروع.
    // النصوص الطويلة Markdown، والقوائم المتكررة (أرقام، مميزات، أسئلة...) JSON لأنها صغيرة ولا تُستعلم منفردة.
    // كل شيء قابل للفراغ فالصفحة تعمل ناقصة، ولا يمس الترحيل أي جدول موجود.
    public function up(): void
    {
        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_published')->default(true);
            $table->json('visible_sections')->nullable();
            $table->string('hero_title', 120)->nullable();
            $table->string('hero_subtitle', 255)->nullable();
            $table->string('hero_image')->nullable();
            $table->string('story_title', 120)->nullable();
            $table->longText('story_body')->nullable();
            $table->string('story_image')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->json('stats')->nullable();
            $table->json('core_values')->nullable();
            $table->json('payment_methods')->nullable();
            $table->longText('shipping_info')->nullable();
            $table->unsignedSmallInteger('return_days')->nullable();
            $table->longText('return_policy')->nullable();
            $table->json('faqs')->nullable();
            $table->string('seo_description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('logo');
            $table->string('url', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('subtitle', 100)->nullable();
            $table->string('quote', 500);
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('avatar')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('city', 100)->nullable();
            $table->text('address');
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp_phone', 30)->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->string('hours_text', 255)->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    // يحذف الجداول الأربعة عند التراجع.
    // لا يمس أي جدول آخر.
    // يُستدعى بـ migrate:rollback فقط.
    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('about_pages');
    }
};
