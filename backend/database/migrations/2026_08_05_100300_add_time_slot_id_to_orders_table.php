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
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->unsignedBigInteger('time_slot_id')->nullable();
            } else {
                $table->foreignId('time_slot_id')->nullable()->constrained()->restrictOnDelete();
            }

            $table->timestampTz('slot_capacity_released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('time_slot_id');
            } else {
                $table->dropConstrainedForeignId('time_slot_id');
            }

            $table->dropColumn('slot_capacity_released_at');
        });
    }
};
