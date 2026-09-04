<?php

namespace Database\Factories;

use App\Enums\LoanType;
use App\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    protected $model = Edition::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'isbn_13' => null,
            'title' => $this->faker->sentence(4),
            'language' => 'en',
            'format' => 'paperback',
            'loan_type' => LoanType::General,
            'special_material' => false,
            'loan_restricted_default' => false,
            'metadata_source' => 'manual',
        ];
    }

    public function reference(): static
    {
        return $this->state(fn (): array => ['loan_type' => LoanType::Reference]);
    }

    public function periodical(): static
    {
        return $this->state(fn (): array => ['loan_type' => LoanType::Periodical]);
    }

    public function specialMaterial(): static
    {
        return $this->state(fn (): array => ['special_material' => true]);
    }

    public function restricted(): static
    {
        return $this->state(fn (): array => ['loan_restricted_default' => true]);
    }

    public function withIsbn(): static
    {
        return $this->state(function (): array {
            $isbn = $this->faker->numerify('978##########');

            return [
                'isbn_13' => $isbn,
                'isbn_10' => null,
            ];
        });
    }
}
