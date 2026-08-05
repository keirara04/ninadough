<?php

namespace Tests\Feature\Notifications;

use App\Jobs\NotifyAdminPaymentSubmitted;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentSubmittedWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_every_active_admin_with_a_phone_number(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]], 200)]);

        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true, 'phone_e164' => '+60111111111']);
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true, 'phone_e164' => '+60122222222']);
        User::factory()->create(['is_active' => true, 'phone_e164' => null]);
        $order = Order::factory()->create(['status' => 'payment_submitted']);

        (new NotifyAdminPaymentSubmitted($order->id))->handle(new WhatsAppService);

        Http::assertSentCount(2);
        $this->assertSame(2, NotificationLog::where('status', 'sent')->count());
    }

    public function test_logs_failure_without_throwing(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400)]);

        User::factory()->create(['role' => 'owner', 'is_active' => true, 'phone_e164' => '+60111111111']);
        $order = Order::factory()->create(['status' => 'payment_submitted']);

        (new NotifyAdminPaymentSubmitted($order->id))->handle(new WhatsAppService);

        $this->assertSame(1, NotificationLog::where('status', 'failed')->count());
    }

    public function test_ignores_inactive_admins_and_admins_without_a_phone(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]], 200)]);

        User::factory()->create(['role' => 'owner', 'is_active' => false, 'phone_e164' => '+60111111111']);
        User::factory()->create(['role' => 'staff', 'is_active' => true, 'phone_e164' => null]);
        $order = Order::factory()->create(['status' => 'payment_submitted']);

        (new NotifyAdminPaymentSubmitted($order->id))->handle(new WhatsAppService);

        Http::assertNothingSent();
        $this->assertSame(0, NotificationLog::count());
    }
}
