<?php

namespace Tests\Feature;

use App\Models\Meditation;
use App\Models\MeditationCategory;
use App\Models\MeditationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeditationApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(
        array $overrides = []
    ): MeditationCategory {
        return MeditationCategory::create(array_merge([
            'name' => 'Relaxation',
            'slug' => 'relaxation',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function makeMeditation(
        array $overrides = []
    ): Meditation {
        return Meditation::create(array_merge([
            'type' => 'guided',
            'title' => 'Calm Body Scan',
            'description' => 'A guided body scan.',
            'duration_minutes' => 10,
            'status' => Meditation::STATUS_PUBLISHED,
            'released_at' => now()->subDay(),
            'language' => 'en',
        ], $overrides));
    }

    private function makeCompletedSession(
        User $user,
        array $overrides = []
    ): MeditationSession {
        return $user->meditationSessions()->create(array_merge([
            'type' => 'guided',
            'status' => MeditationSession::STATUS_COMPLETED,
            'planned_minutes' => 10,
            'actual_minutes' => 10,
            'started_at' => now(),
            'ended_at' => now(),
            'date' => now()->toDateString(),
        ], $overrides));
    }

    public function test_catalog_lists_only_published_content(): void
    {
        $published = $this->makeMeditation();
        $this->makeMeditation([
            'title' => 'Draft Piece',
            'status' => Meditation::STATUS_DESIGN,
        ]);
        $this->makeMeditation([
            'title' => 'Archived Piece',
            'status' => Meditation::STATUS_ARCHIVED,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/meditation')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $meditations = $response->json('data.meditations');

        $this->assertCount(1, $meditations);
        $this->assertSame($published->id, $meditations[0]['id']);
    }

    public function test_catalog_filters_by_type_category_duration_and_query(): void
    {
        $category = $this->makeCategory();

        $this->makeMeditation(['type' => 'guided']);
        $breathing = $this->makeMeditation([
            'title' => 'Box Breathing',
            'type' => 'breathing',
            'category_id' => $category->id,
            'duration_minutes' => 5,
        ]);
        $this->makeMeditation([
            'title' => 'Quiet Sitting',
            'type' => 'free',
            'duration_minutes' => 15,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $byType = $this->getJson('/api/v1/meditation?type=breathing')
            ->assertStatus(200)
            ->json('data.meditations');

        $this->assertCount(1, $byType);
        $this->assertSame($breathing->id, $byType[0]['id']);

        $byCategory = $this->getJson(
            '/api/v1/meditation?category=relaxation'
        )->json('data.meditations');

        $this->assertCount(1, $byCategory);
        $this->assertSame($breathing->id, $byCategory[0]['id']);

        $byDuration = $this->getJson(
            '/api/v1/meditation?max_duration=6'
        )->json('data.meditations');

        $this->assertCount(1, $byDuration);
        $this->assertSame($breathing->id, $byDuration[0]['id']);

        $byQuery = $this->getJson(
            '/api/v1/meditation?q=Quiet'
        )->json('data.meditations');

        $this->assertCount(1, $byQuery);
        $this->assertSame('Quiet Sitting', $byQuery[0]['title']);
    }

    public function test_categories_are_listed_with_published_counts(): void
    {
        $relaxation = $this->makeCategory();
        $this->makeCategory([
            'name' => 'Short Break',
            'slug' => 'short-break',
            'sort_order' => 1,
        ]);
        $hidden = $this->makeCategory([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $this->makeMeditation(['category_id' => $relaxation->id]);
        $this->makeMeditation([
            'category_id' => $relaxation->id,
            'title' => 'Deep Rest',
        ]);
        $this->makeMeditation([
            'category_id' => $relaxation->id,
            'title' => 'Draft In Category',
            'status' => Meditation::STATUS_DESIGN,
        ]);
        $this->makeMeditation([
            'category_id' => $hidden->id,
            'title' => 'Hidden Category Item',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $categories = $this->getJson('/api/v1/meditation/categories')
            ->assertStatus(200)
            ->json('data.categories');

        $this->assertCount(2, $categories);
        $this->assertSame('relaxation', $categories[0]['slug']);
        $this->assertSame(2, $categories[0]['meditations_count']);
        $this->assertSame('short-break', $categories[1]['slug']);
        $this->assertSame(0, $categories[1]['meditations_count']);
    }

    public function test_detail_returns_published_and_hides_unpublished(): void
    {
        $meditation = $this->makeMeditation([
            'license_status' => 'released',
        ]);
        $this->makeMeditation([
            'title' => 'Draft Piece',
            'status' => Meditation::STATUS_DESIGN,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/meditation/{$meditation->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.meditation.id', $meditation->id)
            ->assertJsonPath('data.meditation.license.license_status', 'released');

        $this->getJson('/api/v1/meditation/999999')
            ->assertStatus(404);
    }

    public function test_home_overview_reports_today_week_streak_and_shortcuts(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->makeCompletedSession($user, ['actual_minutes' => 15]);

        $user->goals()->create([
            'goal_type' => 'meditation',
            'name' => 'Weekly practice',
            'target_value' => 5,
            'unit' => 'sessions',
            'period' => 'week',
            'start_date' => now()->startOfWeek()->toDateString(),
        ]);

        $user->streaks()->create([
            'type' => 'meditation',
            'current_streak' => 3,
            'longest_streak' => 5,
        ]);

        $this->makeMeditation();
        $this->makeMeditation([
            'title' => 'Short Reset',
            'duration_minutes' => 3,
        ]);
        $this->makeMeditation([
            'title' => 'Quiet Sitting',
            'type' => 'free',
            'category_id' => null,
            'duration_minutes' => 15,
        ]);

        $reminder = $user->meditationReminders()->create([
            'time' => '07:30',
            'days_of_week' => '0,1,2,3,4,5,6',
            'enabled' => true,
        ]);
        $user->meditationReminders()->create([
            'time' => '09:00',
            'days_of_week' => '0,1,2,3,4,5,6',
            'enabled' => false,
        ]);

        $response = $this->getJson('/api/v1/meditation/home')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.today.minutes', 15)
            ->assertJsonPath('data.today.sessions', 1)
            ->assertJsonPath('data.today.meditated', true)
            ->assertJsonPath('data.week.count', 1)
            ->assertJsonPath('data.week.target', 5)
            ->assertJsonPath('data.streak.current', 3)
            ->assertJsonPath('data.streak.longest', 5)
            ->assertJsonPath('data.shortcuts.guided', 2)
            ->assertJsonPath('data.shortcuts.free', 1)
            ->assertJsonPath('data.shortcuts.breathing', 0)
            ->assertJsonPath('data.reminder.id', $reminder->id)
            ->assertJsonPath('data.reminder.time', '07:30');

        $short = $response->json('data.short_meditations');

        $this->assertCount(1, $short);
        $this->assertSame('Short Reset', $short[0]['title']);
    }

    public function test_home_reports_not_meditating_today(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/meditation/home')
            ->assertStatus(200)
            ->assertJsonPath('data.today.minutes', 0)
            ->assertJsonPath('data.today.sessions', 0)
            ->assertJsonPath('data.today.meditated', false)
            ->assertJsonPath('data.week.count', 0)
            ->assertJsonPath('data.week.target', null)
            ->assertJsonPath('data.streak.current', 0)
            ->assertJsonPath('data.reminder', null);
    }

    public function test_stats_aggregate_completed_sessions(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->makeCompletedSession($user, [
            'type' => 'guided',
            'actual_minutes' => 10,
        ]);
        $this->makeCompletedSession($user, [
            'type' => 'free',
            'actual_minutes' => 15,
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay()->addMinutes(15),
            'date' => now()->subDay()->toDateString(),
        ]);
        $this->makeCompletedSession($user, [
            'type' => 'breathing',
            'status' => MeditationSession::STATUS_CANCELLED,
            'actual_minutes' => null,
            'ended_at' => now(),
        ]);

        $data = $this->getJson('/api/v1/meditation/stats')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_sessions', 2)
            ->assertJsonPath('data.total_minutes', 25)
            ->assertJsonPath('data.total_days', 2)
            ->assertJsonPath('data.average_minutes', 12.5)
            ->assertJsonPath('data.preferred_type', 'free')
            ->assertJsonPath('data.streak.current', 0)
            ->json('data');

        $this->assertCount(14, $data['daily_minutes']);
        $this->assertSame(
            now()->toDateString(),
            $data['daily_minutes'][13]['date']
        );
        $this->assertSame(10, $data['daily_minutes'][13]['minutes']);
        $this->assertSame(15, $data['daily_minutes'][12]['minutes']);
        $this->assertCount(8, $data['weekly']);

        $byType = collect($data['by_type']);
        $guided = $byType->firstWhere('type', 'guided');

        $this->assertSame(1, $guided['sessions']);
        $this->assertSame(10, $guided['minutes']);
    }

    public function test_meditations_can_be_favorited_and_listed(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $meditation = $this->makeMeditation();

        $favorite = $this->postJson('/api/v1/favorites', [
            'type' => 'meditation',
            'id' => $meditation->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.favorite.type', 'meditation')
            ->assertJsonPath('data.favorite.snapshot.title', 'Calm Body Scan')
            ->json('data.favorite');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Meditation::class,
            'favoritable_id' => $meditation->id,
        ]);

        $list = $this->getJson('/api/v1/favorites?type=meditation')
            ->assertStatus(200)
            ->json('data.favorites');

        $this->assertCount(1, $list);
        $this->assertSame('meditation', $list[0]['type']);
        $this->assertSame($meditation->id, $list[0]['target']['id']);

        $this->deleteJson("/api/v1/favorites/{$favorite['id']}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $meditation->id,
        ]);
    }

    public function test_unpublished_meditation_cannot_be_favorited(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $draft = $this->makeMeditation([
            'status' => Meditation::STATUS_DESIGN,
        ]);

        $this->postJson('/api/v1/favorites', [
            'type' => 'meditation',
            'id' => $draft->id,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['id']]);
    }

    public function test_category_can_be_created(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/categories', [
            'name' => 'Self Compassion',
            'description' => 'Kindness toward oneself.',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category.name', 'Self Compassion')
            ->assertJsonPath('data.category.slug', 'self-compassion')
            ->assertJsonPath('data.category.description', 'Kindness toward oneself.');

        $this->assertDatabaseHas('meditation_categories', [
            'slug' => 'self-compassion',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $list = $this->getJson('/api/v1/meditation/categories')
            ->assertStatus(200)
            ->json('data.categories');

        $this->assertTrue(
            collect($list)->contains('slug', 'self-compassion')
        );
    }

    public function test_category_auto_slug_avoids_duplicates(): void
    {
        $this->makeCategory(['name' => 'Focus', 'slug' => 'focus']);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/categories', [
            'name' => 'Focus',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.category.slug', 'focus-2');
    }

    public function test_category_validates_and_enforces_unique_slug(): void
    {
        $this->makeCategory();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->postJson('/api/v1/meditation/categories', [
            'name' => 'Duplicate',
            'slug' => 'relaxation',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);

        $this->postJson('/api/v1/meditation/categories', [
            'name' => 'Bad Slug',
            'slug' => 'Bad Slug',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_meditation_can_be_created(): void
    {
        $category = $this->makeCategory(['slug' => 'mindfulness']);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/meditation', [
            'title' => 'Loving Kindness',
            'type' => 'guided',
            'category_id' => $category->id,
            'duration_minutes' => 12,
            'audio_url' => 'https://audio.longlivy.example/meditations/loving-kindness.mp3',
            'status' => 'published',
            'language' => 'en',
            'source' => 'Longlivy starter content',
            'license_type' => 'proprietary',
            'license_status' => 'released',
            'commercial_use_allowed' => true,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meditation.title', 'Loving Kindness')
            ->assertJsonPath('data.meditation.type', 'guided')
            ->assertJsonPath('data.meditation.duration_minutes', 12)
            ->assertJsonPath('data.meditation.status', 'published')
            ->assertJsonPath('data.meditation.category.slug', 'mindfulness')
            ->assertJsonPath('data.meditation.license.license_status', 'released')
            ->assertJsonPath('data.meditation.license.commercial_use_allowed', true);

        $id = $response->json('data.meditation.id');

        $this->assertDatabaseHas('meditations', [
            'id' => $id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $catalog = $this->getJson('/api/v1/meditation')
            ->assertStatus(200)
            ->json('data.meditations');

        $this->assertCount(1, $catalog);
        $this->assertSame($id, $catalog[0]['id']);
    }

    public function test_created_meditation_applies_defaults_and_stays_draft(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/meditation', [
            'title' => 'Draft Piece',
            'type' => 'free',
            'duration_minutes' => 5,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.meditation.status', 'design')
            ->assertJsonPath('data.meditation.background_type', 'none')
            ->assertJsonPath('data.meditation.language', 'en')
            ->assertJsonPath('data.meditation.version', 1)
            ->assertJsonPath('data.meditation.license.license_status', 'unaudited')
            ->assertJsonPath('data.meditation.license.attribution_required', false);

        $id = $response->json('data.meditation.id');

        $this->getJson('/api/v1/meditation/'.$id)
            ->assertStatus(404);

        $catalog = $this->getJson('/api/v1/meditation')
            ->assertStatus(200)
            ->json('data.meditations');

        $this->assertCount(0, $catalog);
    }

    public function test_meditation_validates_type_category_and_urls(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'type',
                'duration_minutes',
            ]);

        $this->postJson('/api/v1/meditation', [
            'title' => 'Broken',
            'type' => 'sleep',
            'duration_minutes' => 0,
            'category_id' => 999999,
            'audio_url' => 'not-a-url',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'type',
                'duration_minutes',
                'category_id',
                'audio_url',
            ]);
    }
}
