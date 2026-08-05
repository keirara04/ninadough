<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('time_slot_id')->nullable()->after('preorder_date_id')->constrained()->restrictOnDelete();
            $table->timestampTz('slot_capacity_released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('time_slot_id');
            $table->dropColumn('slot_capacity_released_at');
        });
    }
};
