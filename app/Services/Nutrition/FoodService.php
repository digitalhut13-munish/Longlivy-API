<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use App\Models\FoodCategory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FoodService
{
    /**
     * Search the shared catalog plus the user's own foods.
     *
     * The scope decides which rows are visible:
     *   all     -> catalog foods and the user's own foods
     *   catalog -> shared catalog rows only (user_id NULL)
     *   mine    -> only foods the user created themselves
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(
        User $user,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Food::query();

        $scope = $filters['scope'] ?? 'all';
        $scope = ! empty($filters['mine']) ? 'mine' : $scope;

        if ($scope === 'mine') {
            $query->where('user_id', $user->id)
                ->where('is_custom', true);
        } elseif ($scope === 'catalog') {
            $query->where('is_custom', false)
                ->whereNull('user_id');
        } else {
            $query->where(function ($builder) use ($user) {
                $builder->where('is_custom', false)
                    ->orWhere('user_id', $user->id);
            });
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';

            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('brand', 'like', $term);
            });
        }

        if (! empty($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas(
                'category',
                function ($builder) use ($filters) {
                    $builder->where('name', $filters['category'])
                        ->orWhere('slug', $filters['category']);
                }
            );
        }

        if (! empty($filters['barcode'])) {
            $query->where('barcode', $filters['barcode']);
        }

        $sort = $filters['sort'] ?? 'relevance';

        if ($sort === 'relevance' && ! empty($filters['q'])) {
            $query->orderByRaw(
                'CASE WHEN name = ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END',
                [$filters['q'], $filters['q'].'%']
            );
        }

        if ($sort === 'name') {
            $query->orderBy('name');
        }

        if ($sort === 'calories') {
            $query->orderBy('calories');
        }

        if ($sort === 'recent') {
            $query->orderByDesc('created_at');
        }

        // The user's own foods rank above the shared catalog.
        $query->orderByDesc('is_custom');
        $query->orderBy('name');

        $perPage = (int) ($filters['per_page'] ?? $filters['limit'] ?? 50);

        return $query->paginate($perPage)->withQueryString();
    }

    public function categories()
    {
        return FoodCategory::where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, array $data): Food
    {
        $food = new Food($data);

        $food->user_id = $user->id;
        $food->is_custom = true;
        $food->source = $data['source'] ?? 'manual';

        $food->save();

        return $food;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Food $food, array $data): Food
    {
        $food->fill($data);
        $food->save();

        return $food;
    }

    public function delete(Food $food): void
    {
        $food->delete();
    }

    public function findByBarcode(
        User $user,
        string $barcode
    ): ?Food {
        return Food::where('barcode', $barcode)
            ->where(function ($builder) use ($user) {
                $builder->where('is_custom', false)
                    ->orWhere('user_id', $user->id);
            })
            ->orderByRaw('CASE WHEN user_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    /**
     * Cache a product resolved from an external database as a shared
     * catalog food (user_id NULL). Idempotent per barcode so scanning
     * the same code twice never duplicates the row.
     *
     * @param  array<string, mixed>  $data
     */
    public function findOrCreateCatalog(array $data): Food
    {
        $barcode = $data['barcode'] ?? null;
        $source = $data['source'] ?? 'catalog';

        $existing = Food::where('user_id', null)
            ->where('barcode', $barcode)
            ->where('source', $source)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $food = new Food($data);

        $food->user_id = null;
        $food->is_custom = false;
        $food->verified = true;
        $food->source = $source;

        $food->save();

        return $food;
    }

    public function findForUser(User $user, int $foodId): ?Food
    {
        return Food::where('id', $foodId)
            ->where(function ($builder) use ($user) {
                $builder->where('is_custom', false)
                    ->orWhere('user_id', $user->id);
            })
            ->first();
    }
}
