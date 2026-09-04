<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'ulid' => (string) Str::ulid(),
            'name' => Str::title($name),
            'code' => 'LOC-'.strtoupper(Str::random(4)),
            'type' => 'shelf',
            'path' => '/',
            'depth' => 0,
        ];
    }
}
