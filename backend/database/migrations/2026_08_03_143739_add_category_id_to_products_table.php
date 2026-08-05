<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite has no ALTER TABLE ADD FOREIGN KEY — Laravel works around this by
     * rebuilding the whole table (create temp, copy rows, drop, rename), which
     * only preserves Blueprint-tracked constraints and silently drops any raw
     * CHECK constraint added via DB::statement() elsewhere on this table. Skip
     * the FK on sqlite (test-only) to avoid it; Postgres (real deploys) keeps it.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->unsignedBigInteger('category_id')->nullable();
            } else {
                $table->foreignId('category_id')->nullable()
                    ->constrained('product_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('category_id');
            } else {
                $table->dropConstrainedForeignId('category_id');
            }
        });
    }
};
