<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use App\Models\FoodCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FoodService
{
    public function search(User $user, array $filters = []): Collection
    {
        $query = Food::query()
            ->where(function ($builder) use ($user) {
                $builder->where('is_custom', false)
                    ->orWhere('user_id', $user->id);
            });

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

        if (! empty($filters['barcode'])) {
            $query->where('barcode', $filters['barcode']);
        }

        if (! empty($filters['mine'])) {
            $query->where('user_id', $user->id)
                ->where('is_custom', true);
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

        $query->orderBy('name');

        return $query->limit($filters['limit'] ?? 50)->get();
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
