<?php

namespace Database\Factories;

use App\Models\CipherOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CipherOperation>
 */
class CipherOperationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operation' => fake()->randomElement(['encrypt', 'decrypt', 'break']),
            'algorithm' => fake()->randomElement([
                'atbash',
                'caesar',
                'rot13',
                'substitution',
                'affine',
                'vigenere',
                'beaufort',
                'autokey',
                'rail-fence',
                'columnar',
            ]),
            'status' => 'success',
            'input_text' => fake()->sentence(),
            'output_text' => fake()->sentence(),
            'parameters' => ['key' => 'LEMON'],
            'input_hash' => hash('sha256', fake()->sentence()),
            'output_hash' => hash('sha256', fake()->sentence()),
            'recovered_key' => null,
            'score' => null,
            'idioma' => null,
            'duration_ms' => fake()->numberBetween(1, 100),
            'error_message' => null,
        ];
    }
}
