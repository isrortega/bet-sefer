<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'ulid' => (string) Str::ulid(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'path' => '/',
            'depth' => 0,
        ];
    }
}
