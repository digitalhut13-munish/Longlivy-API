<?php

namespace Database\Seeders;

use App\Models\Meditation;
use App\Models\MeditationCategory;
use Illuminate\Database\Seeder;

class MeditationSeeder extends Seeder
{
    /**
     * Starter meditation catalog. Audio files are referenced by URL so
     * the app streams them; only content whose licence permits
     * commercial use may be published in production (licence fields
     * are stored per content object for that check).
     *
     * @var array<int, array<string, mixed>>
     */
    private const MEDITATIONS = [
        [
            'title' => 'Calm Body Scan',
            'type' => 'guided',
            'category' => 'relaxation',
            'duration_minutes' => 10,
            'description' => 'A guided body scan that releases tension from head to toe.',
        ],
        [
            'title' => 'Let Go of Stress',
            'type' => 'guided',
            'category' => 'stress-reduction',
            'duration_minutes' => 15,
            'description' => 'Notice stress in the body and let it soften, breath by breath.',
        ],
        [
            'title' => 'Mindful Breathing',
            'type' => 'guided',
            'category' => 'mindfulness',
            'duration_minutes' => 5,
            'description' => 'A short anchor to the present moment through the breath.',
        ],
        [
            'title' => 'Fall Asleep Peacefully',
            'type' => 'guided',
            'category' => 'fall-asleep',
            'duration_minutes' => 20,
            'description' => 'A slow wind down that guides you gently into sleep.',
        ],
        [
            'title' => 'Morning Clarity',
            'type' => 'guided',
            'category' => 'morning-meditation',
            'duration_minutes' => 10,
            'description' => 'Start the day with a clear mind and a steady intention.',
        ],
        [
            'title' => 'Deep Focus',
            'type' => 'guided',
            'category' => 'concentration',
            'duration_minutes' => 15,
            'description' => 'Gather your attention and settle into focused work.',
        ],
        [
            'title' => 'Short Reset',
            'type' => 'guided',
            'category' => 'short-break',
            'duration_minutes' => 3,
            'description' => 'A three minute reset for a busy day.',
        ],
        [
            'title' => 'Body Awareness',
            'type' => 'guided',
            'category' => 'body-perception',
            'duration_minutes' => 12,
            'description' => 'Reconnect with physical sensations through gentle guidance.',
        ],
        [
            'title' => 'Evening Wind Down',
            'type' => 'guided',
            'category' => 'evening-meditation',
            'duration_minutes' => 10,
            'description' => 'Close the day with a calm and grateful mind.',
        ],
        [
            'title' => 'Box Breathing',
            'type' => 'breathing',
            'category' => 'breathing-meditation',
            'duration_minutes' => 5,
            'description' => 'Equal counts of inhale, hold, exhale and hold.',
        ],
        [
            'title' => '4-7-8 Breathing',
            'type' => 'breathing',
            'category' => 'breathing-meditation',
            'duration_minutes' => 7,
            'description' => 'A long exhale breathing pattern for deep calm.',
        ],
        [
            'title' => 'Quiet Sitting',
            'type' => 'free',
            'category' => null,
            'duration_minutes' => 10,
            'description' => 'Unstructured sitting with optional interval bells.',
        ],
        [
            'title' => 'Open Awareness',
            'type' => 'free',
            'category' => null,
            'duration_minutes' => 15,
            'description' => 'Sit with whatever arises, without guidance.',
        ],
        [
            'title' => 'Your Own Meditation',
            'type' => 'individual',
            'category' => null,
            'duration_minutes' => 7,
            'description' => 'A template for your personal, self-defined meditation.',
        ],
    ];

    public function run(): void
    {
        foreach (self::MEDITATIONS as $meditation) {
            $category = $meditation['category'] !== null
                ? MeditationCategory::where(
                    'slug',
                    $meditation['category']
                )->first()
                : null;

            Meditation::updateOrCreate(
                [
                    'title' => $meditation['title'],
                ],
                [
                    'category_id' => $category?->id,
                    'type' => $meditation['type'],
                    'description' => $meditation['description'],
                    'duration_minutes' => $meditation['duration_minutes'],
                    'audio_url' => $this->audioUrl($meditation['title']),
                    'background_audio_url' => null,
                    'background_type' => 'none',
                    'language' => 'en',
                    'status' => Meditation::STATUS_PUBLISHED,
                    'released_at' => now()->subDays(30),
                    'version' => 1,
                    'source' => 'Longlivy starter content',
                    'rights_holder' => 'Longlivy',
                    'license_type' => 'proprietary',
                    'license_url' => null,
                    'license_status' => 'released',
                    'attribution_required' => false,
                    'commercial_use_allowed' => true,
                ]
            );
        }
    }

    private function audioUrl(string $title): string
    {
        $slug = strtolower(str_replace(' ', '-', $title));

        return "https://audio.longlivy.example/meditations/{$slug}.mp3";
    }
}
