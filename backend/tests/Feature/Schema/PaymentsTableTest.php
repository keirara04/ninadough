<?php

namespace Tests\Feature\Schema;

use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_zero_or_negative_amount_sen(): void
    {
        $this->expectException(QueryException::class);
        Payment::factory()->create(['amount_sen' => 0]);
    }

    public function test_cascades_payment_proofs_when_payment_is_deleted(): void
    {
        $payment = Payment::factory()->create();
        $proof = PaymentProof::factory()->for($payment)->create();

        $payment->delete();

        $this->assertNull(PaymentProof::find($proof->id));
    }
}
