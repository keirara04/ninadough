<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 160);
            $table->string('phone_e164', 20)->unique();
            $table->string('email', 255)->nullable();
            $table->timestampTz('marketing_consent_at')->nullable();
            $table->timestampTz('last_order_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('last_order_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
