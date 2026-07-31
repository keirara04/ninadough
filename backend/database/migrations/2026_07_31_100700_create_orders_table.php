<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('preorder_date_id')->constrained()->restrictOnDelete();
            $table->string('checkout_channel', 20);
            $table->string('fulfilment_method', 20);
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name_snapshot', 160);
            $table->string('customer_phone_snapshot', 20);
            $table->string('customer_email_snapshot', 255)->nullable();
            $table->jsonb('delivery_address')->nullable();
            $table->text('pickup_instruction_snapshot')->nullable();
            $table->bigInteger('subtotal_sen');
            $table->bigInteger('delivery_fee_sen');
            $table->bigInteger('discount_sen')->default(0);
            $table->bigInteger('total_sen');
            $table->integer('total_capacity_units');
            $table->string('status', 30);
            $table->string('payment_status', 30);
            $table->string('payment_method', 30)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('capacity_released_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->jsonb('source_metadata')->nullable();
            $table->timestampsTz();

            $table->index(['preorder_date_id', 'status']);
            $table->index(['preorder_date_id', 'fulfilment_method', 'status']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['checkout_channel', 'created_at']);
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_checkout_channel_check CHECK (checkout_channel IN ('website', 'whatsapp'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_fulfilment_method_check CHECK (fulfilment_method IN ('pickup', 'delivery'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN (
            'whatsapp_pending', 'awaiting_payment', 'payment_submitted', 'payment_confirmed',
            'preparing', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled', 'rejected', 'expired'
        ))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status IN (
            'not_required', 'awaiting_payment', 'submitted', 'paid', 'failed', 'refunded'
        ))");
        DB::statement(
            'ALTER TABLE orders ADD CONSTRAINT orders_fulfilment_fields_check CHECK ('.
            "(fulfilment_method = 'delivery' AND delivery_address IS NOT NULL AND delivery_zone_id IS NOT NULL) OR ".
            "(fulfilment_method = 'pickup' AND delivery_address IS NULL AND delivery_zone_id IS NULL)".
            ')'
        );
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_whatsapp_pending_expiry_check CHECK (status <> 'whatsapp_pending' OR expires_at IS NOT NULL)");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_subtotal_check CHECK (subtotal_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_delivery_fee_check CHECK (delivery_fee_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_discount_check CHECK (discount_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_check CHECK (total_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_capacity_units_check CHECK (total_capacity_units > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
