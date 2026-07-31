<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 50);
            $table->string('object_key', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestampTz('uploaded_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note_internal')->nullable();
            $table->timestampsTz();

            $table->index(['payment_id', 'uploaded_at']);
            $table->index('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
