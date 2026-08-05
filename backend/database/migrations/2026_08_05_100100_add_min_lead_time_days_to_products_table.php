<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('min_lead_time_days')->default(0);
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_min_lead_time_days_check CHECK (min_lead_time_days >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP CONSTRAINT products_min_lead_time_days_check');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('min_lead_time_days');
        });
    }
};
