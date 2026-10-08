<?php

namespace Database\Seeders;

use App\Models\MeditationCategory;
use Illuminate\Database\Seeder;

class MeditationCategorySeeder extends Seeder
{
    /**
     * Categories used to filter guided meditations. The slug is the
     * stable identifier and is managed by the backend, so new
     * categories can be added later without app changes.
     *
     * @var array<int, array{name: string, slug: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Relaxation', 'slug' => 'relaxation'],
        ['name' => 'Stress Reduction', 'slug' => 'stress-reduction'],
        ['name' => 'Mindfulness', 'slug' => 'mindfulness'],
        ['name' => 'Breathing Meditation', 'slug' => 'breathing-meditation'],
        ['name' => 'Fall Asleep', 'slug' => 'fall-asleep'],
        ['name' => 'Morning Meditation', 'slug' => 'morning-meditation'],
        ['name' => 'Concentration', 'slug' => 'concentration'],
        ['name' => 'Short Break', 'slug' => 'short-break'],
        ['name' => 'Body Perception', 'slug' => 'body-perception'],
        ['name' => 'Evening Meditation', 'slug' => 'evening-meditation'],
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
