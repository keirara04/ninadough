<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->string('card_message', 200)->nullable();
            $table->text('allergies_note')->nullable();
            $table->boolean('hide_price_on_package')->default(false);
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IS NULL OR payment_method = 'bank_transfer')");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method = 'bank_transfer')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP CONSTRAINT payments_method_check');
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['notes', 'card_message', 'allergies_note', 'hide_price_on_package']);
        });
    }
};
