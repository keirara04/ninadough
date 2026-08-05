<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('rejection_message', 500)->nullable()->after('payment_method');
            $table->boolean('refund_required')->default(false)->after('rejection_message');
            $table->text('refund_note')->nullable()->after('refund_required');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['rejection_message', 'refund_required', 'refund_note']);
        });
    }
};
