<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->nullable();
            $table->string('recipient_name', 160);
            $table->string('recipient_phone_e164', 20);
            $table->string('line_1', 255);
            $table->string('line_2', 255)->nullable();
            $table->string('city', 120);
            $table->string('state', 120);
            $table->string('postcode', 20);
            $table->char('country_code', 2)->default('MY');
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['customer_id', 'is_default']);
            $table->index('postcode');
        });

        DB::statement(
            'CREATE UNIQUE INDEX customer_addresses_one_default_per_customer '.
            'ON customer_addresses (customer_id) WHERE is_default = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
