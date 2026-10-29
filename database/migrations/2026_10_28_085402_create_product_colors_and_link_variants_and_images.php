<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('hex', 7)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'name']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('product_color_id')->nullable()->after('product_id')
                ->constrained('product_colors')->nullOnDelete();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_color_id')->nullable()->after('product_id')
                ->constrained('product_colors')->cascadeOnDelete();

            $table->index(['product_id', 'product_color_id', 'sort_order']);
        });

        $this->backfill();
    }

    /** ينشئ لونًا لكل (منتج، اسم لون) موجود ويربط خياراته به. */
    private function backfill(): void
    {
        DB::transaction(function (): void {
            $variants = DB::table('product_variants')
                ->orderBy('id')
                ->get(['id', 'product_id', 'color', 'color_hex'])
                ->groupBy('product_id');

            foreach ($variants as $productId => $productVariants) {
                // التجميع بدون حساسية لحالة الأحرف، كما يفعل فهرس قاعدة البيانات
                $byColor = $productVariants->groupBy(fn ($v) => mb_strtolower(trim($v->color)));

                $order = 0;

                foreach ($byColor as $group) {
                    $hex = $group->pluck('color_hex')
                        ->map(fn ($h) => strtolower((string) $h))
                        ->first(fn ($h) => preg_match('/^#[0-9a-f]{6}$/', $h) === 1);

                    $colorId = DB::table('product_colors')->insertGetId([
                        'product_id' => $productId,
                        'name' => trim($group->first()->color),
                        'hex' => $hex,
                        'sort_order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('product_variants')
                        ->whereIn('id', $group->pluck('id'))
                        ->update(['product_color_id' => $colorId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'product_color_id', 'sort_order']);
            $table->dropConstrainedForeignId('product_color_id');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_color_id');
        });

        Schema::dropIfExists('product_colors');
    }
};
