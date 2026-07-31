<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('method', 30);
            $table->string('provider', 60)->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->bigInteger('amount_sen');
            $table->char('currency', 3)->default('MYR');
            $table->string('status', 20);
            $table->timestampTz('paid_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('gateway_payload')->nullable();
            $table->timestampsTz();

            $table->index(['order_id', 'created_at']);
            $table->index(['provider', 'provider_reference']);
            $table->index(['status', 'created_at']);
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount_sen > 0)');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'submitted', 'confirmed', 'failed', 'refunded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
