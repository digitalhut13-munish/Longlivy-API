<?php

namespace Database\Seeders;

use App\Models\Meditation;
use App\Models\MeditationCategory;
use Illuminate\Database\Seeder;

class MeditationSeeder extends Seeder
{
    /**
     * Starter meditation catalog. Audio is stored per language in the
     * meditation_audio table so the catalog can serve streamable URLs
     * (see requirement #6). Only content whose licence permits
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
            'breathing_pattern' => [
                'inhale_seconds' => 4,
                'hold_seconds' => 4,
                'exhale_seconds' => 4,
                'second_hold_seconds' => 4,
                'repetitions' => 8,
            ],
        ],
        [
            'title' => '4-7-8 Breathing',
            'type' => 'breathing',
            'category' => 'breathing-meditation',
            'duration_minutes' => 7,
            'description' => 'A long exhale breathing pattern for deep calm.',
            'breathing_pattern' => [
                'inhale_seconds' => 4,
                'hold_seconds' => 7,
                'exhale_seconds' => 8,
                'second_hold_seconds' => 0,
                'repetitions' => 8,
            ],
        ],
        [
            'title' => 'Nadi Shodhana',
            'type' => 'breathing',
            'category' => 'breathing-meditation',
            'duration_minutes' => 5,
            'description' => 'Alternate nostril breathing to balance the mind.',
            'breathing_pattern' => [
                'inhale_seconds' => 4,
                'hold_seconds' => 0,
                'exhale_seconds' => 4,
                'second_hold_seconds' => 0,
                'repetitions' => 10,
            ],
        ],
        [
            'title' => 'Quiet Sitting',
            'type' => 'free',
            'category' => null,
            'duration_minutes' => 10,
            'sound_category' => 'ambient',
            'description' => 'Unstructured sitting with optional interval bells.',
        ],
        [
            'title' => 'Open Awareness',
            'type' => 'free',
            'category' => null,
            'duration_minutes' => 15,
            'sound_category' => 'nature',
            'description' => 'Sit with whatever arises, without guidance.',
        ],
        [
            'title' => 'Sound Bath',
            'type' => 'free',
            'category' => null,
            'duration_minutes' => 20,
            'sound_category' => 'meditation_music',
            'description' => 'Soft instrumental beds for an unguided practice.',
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

            $record = Meditation::updateOrCreate(
                [
                    'title' => $meditation['title'],
                ],
                [
                    'category_id' => $category?->id,
                    'type' => $meditation['type'],
                    'description' => $meditation['description'],
                    'duration_minutes' => $meditation['duration_minutes'],
                    'duration_seconds' => $meditation['duration_minutes'] * 60,
                    'sound_category' => $meditation['sound_category'] ?? null,
                    'thumbnail_path' => $this->thumbnailUrl(
                        $meditation['title']
                    ),
                    'availability' => Meditation::AVAILABILITY_AVAILABLE,
                    'is_premium' => false,
                    'breathing_pattern' => $meditation['breathing_pattern'] ?? null,
                    'audio_url' => $this->audioUrl(
                        $meditation['title'],
                        'en'
                    ),
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

            foreach (['en', 'de'] as $language) {
                $record->audio()->updateOrCreate(
                    ['language' => $language],
                    [
                        'storage_path' => $this->audioUrl(
                            $meditation['title'],
                            $language
                        ),
                        'format' => 'mp3',
                        'mime_type' => 'audio/mpeg',
                        'bitrate_kbps' => 128,
                        'duration_seconds' => $meditation['duration_minutes'] * 60,
                        'size_bytes' => $meditation['duration_minutes'] * 60 * 16000,
                    ]
                );
            }
        }
    }

    private function thumbnailUrl(string $title): string
    {
        $slug = strtolower(str_replace(' ', '-', $title));

        return "https://cdn.longlivy.example/meditations/{$slug}/thumb@2x.jpg";
    }

    private function audioUrl(string $title, string $language): string
    {
        $slug = strtolower(str_replace(' ', '-', $title));

        return "https://audio.longlivy.example/meditations/{$slug}_{$language}.mp3";
    }
}