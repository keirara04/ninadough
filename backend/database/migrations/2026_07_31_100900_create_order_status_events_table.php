<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('actor_type', 20);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note_internal')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
            $table->index(['to_status', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });

        DB::statement("ALTER TABLE order_status_events ADD CONSTRAINT order_status_events_actor_type_check CHECK (actor_type IN ('user', 'system'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_events');
    }
};
