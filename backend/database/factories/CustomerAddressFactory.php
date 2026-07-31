<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'recipient_name' => $this->faker->name(),
            'recipient_phone_e164' => '+601'.$this->faker->numerify('########'),
            'line_1' => $this->faker->streetAddress(),
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan',
            'postcode' => $this->faker->numerify('#####'),
            'country_code' => 'MY',
            'is_default' => false,
        ];
    }
}
