<?php

namespace App\Services\Meditation;

use App\Models\Goal;
use App\Models\GoalProgress;
use App\Models\Meditation;
use App\Models\MeditationCategory;
use App\Models\MeditationReminder;
use App\Models\MeditationSession;
use App\Models\User;
use App\Services\Goal\GoalService;
use App\Services\Streak\StreakService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MeditationService
{
    public function __construct(
        private readonly StreakService $streakService,
        private readonly GoalService $goalService
    ) {}

    public function categories(): Collection
    {
        return MeditationCategory::query()
            ->where('is_active', true)
            ->withCount([
                'meditations' => function ($query) {
                    $query->where(
                        'status',
                        Meditation::STATUS_PUBLISHED
                    );
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function catalog(
        ?User $user = null,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Meditation::query()
            ->where('status', Meditation::STATUS_PUBLISHED)
            ->with('category', 'audio')
            ->with($this->favoriteScope($user));

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', function ($builder) use ($filters) {
                $builder->where('slug', $filters['category']);
            });
        }

        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (isset($filters['max_duration'])) {
            $query->where(
                'duration_minutes',
                '<=',
                (int) $filters['max_duration']
            );
        }

        if (! empty($filters['q'])) {
            $query->where(function ($builder) use ($filters) {
                $builder->where('title', 'like', "%{$filters['q']}%")
                    ->orWhere('description', 'like', "%{$filters['q']}%");
            });
        }

        return $query
            ->orderByDesc('released_at')
            ->orderBy('title')
            ->paginate(
                (int) ($filters['per_page'] ?? 50)
            )
            ->withQueryString();
    }

    public function find(int $id, ?User $user = null): Meditation
    {
        return Meditation::where('id', $id)
            ->where('status', Meditation::STATUS_PUBLISHED)
            ->with('category', 'audio')
            ->with($this->favoriteScope($user))
            ->firstOrFail();
    }

    private function favoriteScope(?User $user): array
    {
        if ($user === null) {
            return ['favorites' => fn ($query) => $query->whereRaw('1 = 0')];
        }

        return [
            'favorites' => fn ($query) => $query->where('user_id', $user->id),
        ];
    }

    public function createCategory(array $data): MeditationCategory
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        if ($slug === '') {
            $slug = Str::slug(Str::random(12));
        }

        return MeditationCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueCategorySlug(
                Str::limit($slug, 100, '')
            ),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function createMeditation(array $data): Meditation
    {
        $meditation = Meditation::create(array_merge([
            'category_id' => null,
            'description' => null,
            'audio_url' => null,
            'background_audio_url' => null,
            'background_type' => 'none',
            'language' => 'en',
            'status' => Meditation::STATUS_DESIGN,
            'released_at' => null,
            'version' => 1,
            'source' => null,
            'rights_holder' => null,
            'license_type' => null,
            'license_url' => null,
            'license_status' => 'unaudited',
            'attribution_required' => false,
            'commercial_use_allowed' => false,
        ], $data));

        return $meditation->load('category');
    }

    private function uniqueCategorySlug(string $slug): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (
            MeditationCategory::where('slug', $candidate)->exists()
        ) {
            $candidate = Str::limit(
                "{$slug}-{$suffix}",
                100,
                ''
            );
            $suffix++;
        }

        return $candidate;
    }

    public function home(User $user): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $todaySessions = $user->meditationSessions()
            ->where('status', MeditationSession::STATUS_COMPLETED)
            ->whereDate('date', $today)
            ->get();

        $todayMinutes = (int) $todaySessions->sum('actual_minutes');

        $weekCount = $user->meditationSessions()
            ->where('status', MeditationSession::STATUS_COMPLETED)
            ->whereDate('date', '>=', $weekStart)
            ->count();

        $weekGoal = $user->goals()
            ->where('goal_type', 'meditation')
            ->where('active', true)
            ->where('period', 'week')
            ->latest()
            ->first();

        $streak = $this->streakService->get($user, 'meditation');

        $shortcuts = Meditation::query()
            ->where('status', Meditation::STATUS_PUBLISHED)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $shortMeditations = Meditation::query()
            ->where('status', Meditation::STATUS_PUBLISHED)
            ->where('duration_minutes', '<=', 5)
            ->orderBy('duration_minutes')
            ->limit(5)
            ->get();

        $reminder = $user->meditationReminders()
            ->where('enabled', true)
            ->get()
            ->filter(fn (MeditationReminder $reminder) => $reminder
                ->runsOnDay((int) now()->dayOfWeek))
            ->sortBy('time')
            ->first();

        $minStreakMinutes = (int) config(
            'longlivy.meditation.min_streak_minutes'
        );

        return [
            'today' => [
                'date' => $today,
                'minutes' => $todayMinutes,
                'sessions' => $todaySessions->count(),
                'meditated' => $todayMinutes >= $minStreakMinutes,
            ],
            'week' => [
                'start_date' => $weekStart,
                'count' => $weekCount,
                'target' => $weekGoal !== null
                    ? (float) $weekGoal->target_value
                    : null,
            ],
            'streak' => [
                'current' => $streak->current_streak,
                'longest' => $streak->longest_streak,
            ],
            'shortcuts' => [
                'guided' => (int) $shortcuts->get('guided', 0),
                'free' => (int) $shortcuts->get('free', 0),
                'breathing' => (int) $shortcuts->get('breathing', 0),
                'individual' => (int) $shortcuts->get('individual', 0),
            ],
            'short_meditations' => $shortMeditations,
            'reminder' => $reminder,
        ];
    }

    public function stats(User $user): array
    {
        $completed = $user->meditationSessions()
            ->where('status', MeditationSession::STATUS_COMPLETED)
            ->with('meditation.category')
            ->get();

        $totalMinutes = (int) $completed->sum('actual_minutes');
        $totalCount = $completed->count();
        $totalDays = $completed->pluck('date')
            ->unique()
            ->count();

        $byType = $completed
            ->groupBy('type')
            ->map(fn (Collection $sessions, string $type) => [
                'type' => $type,
                'sessions' => $sessions->count(),
                'minutes' => (int) $sessions->sum('actual_minutes'),
            ])
            ->values();

        $byCategory = $completed
            ->filter(fn ($session) => $session->meditation?->category !== null)
            ->groupBy(fn ($session) => $session
                ->meditation->category->name)
            ->map(fn (Collection $sessions, string $name) => [
                'category' => $name,
                'sessions' => $sessions->count(),
                'minutes' => (int) $sessions->sum('actual_minutes'),
            ])
            ->values()
            ->sortByDesc('minutes')
            ->values();

        $topType = $byType
            ->sortByDesc('minutes')
            ->first();

        $preferredType = is_array($topType)
            ? ($topType['type'] ?? null)
            : null;

        $dailyMinutes = collect(range(13, 0))
            ->map(function (int $offset) use ($completed) {
                $date = now()->subDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'minutes' => (int) $completed
                        ->filter(fn ($session) => $session
                            ->date->isSameDay($date))
                        ->sum('actual_minutes'),
                ];
            });

        $weekly = collect(range(7, 0))
            ->map(function (int $offset) use ($completed) {
                $start = now()
                    ->subWeeks($offset)
                    ->startOfWeek();

                $sessions = $completed->filter(fn ($session) => $session
                    ->date->between($start, $start->copy()->endOfWeek()));

                return [
                    'week_start' => $start->toDateString(),
                    'sessions' => $sessions->count(),
                    'minutes' => (int) $sessions->sum('actual_minutes'),
                ];
            });

        $streak = $this->streakService->get($user, 'meditation');

        return [
            'total_sessions' => $totalCount,
            'total_minutes' => $totalMinutes,
            'total_days' => $totalDays,
            'average_minutes' => $totalCount > 0
                ? round($totalMinutes / $totalCount, 1)
                : 0,
            'preferred_type' => $preferredType,
            'by_type' => $byType,
            'by_category' => $byCategory,
            'daily_minutes' => $dailyMinutes,
            'weekly' => $weekly,
            'streak' => [
                'current' => $streak->current_streak,
                'longest' => $streak->longest_streak,
            ],
        ];
    }

    public function goals(User $user): Collection
    {
        return $user->goals()
            ->where('goal_type', 'meditation')
            ->where('active', true)
            ->latest()
            ->get();
    }

    public function createGoal(User $user, array $data): Goal
    {
        return $user->goals()->create([
            ...$data,
            'goal_type' => 'meditation',
        ]);
    }

    public function findGoal(User $user, int $goalId): Goal
    {
        return $user->goals()
            ->where('goal_type', 'meditation')
            ->findOrFail($goalId);
    }

    public function updateGoal(Goal $goal, array $data): Goal
    {
        $goal->update($data);

        return $goal->fresh();
    }

    public function deleteGoal(Goal $goal): void
    {
        $goal->delete();
    }

    public function goalProgress(Goal $goal): Collection
    {
        return $this->goalService->getProgress($goal);
    }

    public function recordGoalProgress(
        Goal $goal,
        array $data
    ): GoalProgress {
        return $goal->progress()->updateOrCreate(
            [
                'date' => $data['date'],
            ],
            [
                'value' => $data['value'],
                'completed' => (float) $data['value']
                    >= (float) $goal->target_value,
            ]
        );
    }
}
