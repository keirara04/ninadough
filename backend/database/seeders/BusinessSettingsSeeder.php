<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'business_name' => 'ninadough',
            'timezone' => 'Asia/Kuala_Lumpur',
            'whatsapp_number' => '+60123456789',
            'default_whatsapp_reservation_minutes' => 20,
            'checkout_mode' => 'hybrid',
            'bank_transfer_instructions' => 'Transfer to Maybank 1234567890, then send proof via WhatsApp.',
            'pickup_instructions' => 'Pickup at ninadough kitchen, 10am-6pm.',
            'privacy_contact_email' => 'privacy@ninadough.test',
        ];

        foreach ($settings as $key => $value) {
            BusinessSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
