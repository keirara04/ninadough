<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentProofUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Payment-proof upload dispatches NotifyAdminPaymentSubmitted (WhatsApp);
        // fake the queue so tests never make a real network call.
        Queue::fake();
    }

    private function signedUploadUrl(Order $order): string
    {
        return URL::temporarySignedRoute('orders.payment-proof', now()->addMinutes(5), ['reference' => $order->order_number]);
    }

    public function test_uploaded_proof_lands_on_private_disk_not_public(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create(['status' => 'awaiting_payment']);
        Payment::factory()->for($order)->create(['status' => 'pending']);

        $response = $this->postJson($this->signedUploadUrl($order), [
            'proof' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertOk();
        $proof = PaymentProof::first();
        $this->assertSame('local', $proof->storage_disk);
        Storage::disk('local')->assertExists($proof->object_key);
    }

    public function test_public_url_never_exposed_in_status_response(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create(['status' => 'awaiting_payment']);
        Payment::factory()->for($order)->create(['status' => 'pending']);

        $response = $this->postJson($this->signedUploadUrl($order), [
            'proof' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('data.proof_url');
        $this->assertStringNotContainsString('/storage/', $response->getContent());
    }

    public function test_non_whitelisted_mime_rejected(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create(['status' => 'awaiting_payment']);
        Payment::factory()->for($order)->create(['status' => 'pending']);

        $response = $this->postJson($this->signedUploadUrl($order), [
            'proof' => UploadedFile::fake()->create('receipt.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('proof');
    }

    public function test_second_upload_while_pending_supersedes_first(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create(['status' => 'awaiting_payment']);
        Payment::factory()->for($order)->create(['status' => 'pending']);

        $this->postJson($this->signedUploadUrl($order), ['proof' => UploadedFile::fake()->image('first.jpg')]);
        $order->refresh();
        $this->postJson($this->signedUploadUrl($order), ['proof' => UploadedFile::fake()->image('second.jpg')]);

        $this->assertSame(2, PaymentProof::count());
        $this->assertNotNull(PaymentProof::orderBy('id')->first()->superseded_at);
        $this->assertNull(PaymentProof::orderByDesc('id')->first()->superseded_at);
    }

    public function test_reupload_blocked_once_payment_confirmed(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create(['status' => 'payment_confirmed']);
        Payment::factory()->for($order)->create(['status' => 'confirmed']);

        $response = $this->postJson($this->signedUploadUrl($order), [
            'proof' => UploadedFile::fake()->image('late.jpg'),
        ]);

        $response->assertStatus(404);
    }

    public function test_admin_proof_route_requires_authentication(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->for($order)->create();
        $proof = PaymentProof::factory()->for($payment)->create(['storage_disk' => 'local']);

        $response = $this->getJson("/api/v1/admin/payment-proofs/{$proof->id}");

        $response->assertStatus(401);
    }
}
