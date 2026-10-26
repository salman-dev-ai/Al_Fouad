
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add only columns that do not already exist.
        if (!Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('payment_method', 20)
                    ->default('cod')
                    ->after('delivery_fee');
            });
        }

        if (!Schema::hasColumn('orders', 'location_url')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('location_url', 2048)
                    ->nullable()
                    ->after('notes');
            });
        }

        // Make customer phone unique only if it is not already unique.
        if (Schema::hasTable('customers')) {
            $indexes = Schema::getIndexes('customers');

            $phoneIsUnique = collect($indexes)->contains(
                fn ($index) =>
                    $index['unique'] &&
                    $index['columns'] === ['phone']
            );

            if (!$phoneIsUnique) {
                Schema::table('customers', function (Blueprint $table) use ($indexes) {
                    $phoneIndex = collect($indexes)->first(
                        fn ($index) =>
                            !$index['unique'] &&
                            $index['columns'] === ['phone']
                    );

                    if ($phoneIndex) {
                        $table->dropIndex($phoneIndex['name']);
                    }

                    $table->unique('phone');
                });
            }
        }
    }

    public function down(): void
    {
        // Remove only columns added by this migration.
        // Existing columns are preserved.
        if (Schema::hasColumn('orders', 'location_url')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('location_url');
            });
        }

        if (Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }

        // Do not automatically remove the unique phone index here,
        // because it may have existed before this migration.
    }
};
