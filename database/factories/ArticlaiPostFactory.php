<?php

namespace Articlai\Articlai\Database\Factories;

use Articlai\Articlai\Models\ArticlaiPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticlaiPostFactory extends Factory
{
    protected $model = ArticlaiPost::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);
        
        return [
            'title' => $title,
            'content' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->sentence(10),
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1, 1000),
            'meta_title' => $this->faker->sentence(6),
            'meta_description' => $this->faker->sentence(15),
            'focus_keyword' => $this->faker->word(),
            'canonical_url' => $this->faker->url(),
            'published_at' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'custom_fields' => [
                'author' => $this->faker->name(),
                'source' => 'automated',
                'category' => $this->faker->word(),
            ],
            'status' => $this->faker->randomElement(['draft', 'published', 'private']),
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'private',
        ]);
    }
}
