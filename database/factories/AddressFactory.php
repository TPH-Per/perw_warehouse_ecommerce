<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'recipient_name' => $this->faker->name,
            'recipient_phone' => $this->faker->phoneNumber,
            'street_address' => $this->faker->streetAddress,
            'ward' => $this->faker->citySuffix,
            'district' => $this->faker->state,
            'city' => $this->faker->city,
            'is_default' => $this->faker->boolean,
        ];
    }
}
