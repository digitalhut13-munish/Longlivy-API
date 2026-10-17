<?php

namespace Database\Seeders;

use App\Models\MeditationCategory;
use Illuminate\Database\Seeder;

class MeditationCategorySeeder extends Seeder
{
    /**
     * Categories used to filter meditations. The slug is the stable
     * identifier the app matches against, so these values are the
     * canonical set the client recognises:
     * morning, mindfulness, energy, focus, relaxation, calm,
     * stress_relief, sleep.
     *
     * @var array<int, array{name: string, slug: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Morning', 'slug' => 'morning'],
        ['name' => 'Mindfulness', 'slug' => 'mindfulness'],
        ['name' => 'Energy', 'slug' => 'energy'],
        ['name' => 'Focus', 'slug' => 'focus'],
        ['name' => 'Relaxation', 'slug' => 'relaxation'],
        ['name' => 'Calm', 'slug' => 'calm'],
        ['name' => 'Stress Relief', 'slug' => 'stress_relief'],
        ['name' => 'Sleep', 'slug' => 'sleep'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $position => $category) {
            MeditationCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $position,
                    'is_active' => true,
                ]
            );
        }
    }
}
