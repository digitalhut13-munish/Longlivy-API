<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\MealRecognitionService;
use App\Models\Food;
use App\Models\User;
use App\Services\Nutrition\FoodService;

/**
 * Local, provider-light recognition for text and voice drafts.
 *
 * Free-text descriptions are parsed into "<amount> <unit> <food>" or
 * "<count> <food>" segments ("100 g rice", "2 eggs") and each segment
 * is matched against the food database (shared catalog plus the user's
 * own foods) so the draft carries food_id and a nutrient snapshot for
 * the stated quantity, ready for review.
 *
 * Every returned value is an estimate produced by keyword matching -
 * not a laboratory analysis - and drafts are always flagged for review.
 * Add a vision/LLM provider behind this contract once one is contracted.
 *
 * The authenticated user is passed through the payload ("user").
 */
class AiMealRecognitionService implements MealRecognitionService
{
    public function __construct(
        private readonly FoodService $foodService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function recognize(string $type, array $payload): array
    {
        if (! in_array($type, ['text', 'voice'], true)) {
            return [
                'meal_type' => $this->declaredMealType($payload),
                'items' => [],
                'confidence' => 0.0,
                'needs_review' => true,
                'provider' => $this->providerName(),
            ];
        }

        $text = trim((string) ($payload['text'] ?? ''));

        if ($text === '') {
            return [
                'meal_type' => $this->declaredMealType($payload),
                'items' => [],
                'confidence' => 0.0,
                'needs_review' => true,
                'provider' => $this->providerName(),
            ];
        }

        $user = $payload['user'] instanceof User ? $payload['user'] : null;

        $items = [];
        $confidences = [];

        foreach ($this->parseSegments($text) as $segment) {
            $item = $this->resolveItem($user, $segment);

            $items[] = $item;

            if (isset($item['confidence'])) {
                $confidences[] = (float) $item['confidence'];
            }
        }

        $confidence = $confidences !== []
            ? round(array_sum($confidences) / count($confidences), 2)
            : 0.4;

        return [
            'meal_type' => $this->guessMealType($text, $payload),
            'items' => $items,
            'confidence' => $confidence,
            'needs_review' => true,
            'provider' => $this->providerName(),
        ];
    }

    /**
     * Parses both "<amount> <unit> <food>" ("100 g rice") and
     * "<count> <food>" ("2 eggs") segments.
     *
     * @return array<int, array{qty: float, unit: string, name: string}>
     */
    private function parseSegments(string $text): array
    {
        $pattern = '/(?<qty>\d+(?:[.,]\d+)?)\s*(?:of\s+)?'
            .'(?:(?<unit>grams?|g|milliliters?|ml|pieces?|pcs|slices?|cups?)\s+)?'
            .'(?:of\s+)?'
            .'(?<name>[a-zäöüß]+(?:\s+[a-zäöüß]+)*?)'
            .'(?=\s+(?:for|with|and|plus|of)\b|\s+\d|\s*[,.!?;]|$)/iu';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $segments = [];

        foreach ($matches as $match) {
            $segments[] = [
                'qty' => (float) str_replace(',', '.', $match['qty']),
                'unit' => $this->normalizeUnit($match['unit'] ?? ''),
                'name' => trim($match['name']),
            ];
        }

        return $segments;
    }

    /**
     * @param  array{qty: float, unit: string, name: string}  $segment
     * @return array<string, mixed>
     */
    private function resolveItem(?User $user, array $segment): array
    {
        $food = $user !== null
            ? $this->bestMatch($user, $segment['name'])
            : null;

        if ($food === null) {
            return [
                'food_id' => null,
                'name' => ucfirst($segment['name']),
                'quantity' => $this->presentQuantity($segment['qty']),
                'unit' => $segment['unit'],
                'nutrients' => null,
                'confidence' => 0.3,
                'estimated' => true,
            ];
        }

        [$factor, $confidence] = $this->conversion(
            $food,
            $segment['qty'],
            $segment['unit']
        );

        return [
            'food_id' => $food->id,
            'name' => $food->name,
            'quantity' => $this->presentQuantity($segment['qty']),
            'unit' => $segment['unit'],
            'nutrients' => [
                'calories' => round((float) $food->calories * $factor, 2),
                'protein' => round((float) $food->protein * $factor, 2),
                'carbohydrates' => round((float) $food->carbohydrates * $factor, 2),
                'fat' => round((float) $food->fat * $factor, 2),
                'fiber' => $food->fiber !== null
                    ? round((float) $food->fiber * $factor, 2)
                    : null,
            ],
            'confidence' => $confidence,
            'estimated' => true,
        ];
    }

    /**
     * Whole numbers are returned as integers ("2 eggs", not "2.0").
     */
    private function presentQuantity(float $qty): int|float
    {
        return floor($qty) === $qty ? (int) $qty : $qty;
    }

    /**
     * How many base units (g/ml) the stated quantity represents,
     * expressed as a factor of the food's base_amount, plus the
     * confidence of that conversion.
     *
     * @return array{0: float, 1: float}
     */
    private function conversion(Food $food, float $qty, string $unit): array
    {
        $baseAmount = max((float) $food->base_amount, 0.0001);
        $isLiquid = $food->base_unit === 'ml';

        return match ($unit) {
            'g', 'ml' => [
                $qty / $baseAmount,
                ($unit === 'g') === ! $isLiquid ? 0.82 : 0.7,
            ],
            'cup' => [
                ($qty * 240) / $baseAmount,
                0.6,
            ],
            default => $this->pieceConversion($food, $qty, $baseAmount),
        };
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function pieceConversion(
        Food $food,
        float $qty,
        float $baseAmount
    ): array {
        $perUnit = $food->base_unit === 'ml'
            ? (float) ($food->ml_per_unit ?? 0)
            : (float) ($food->grams_per_unit ?? 0);

        if ($perUnit <= 0) {
            $perUnit = (float) ($food->serving_amount ?? 0);
        }

        if ($perUnit > 0) {
            return [($qty * $perUnit) / $baseAmount, 0.75];
        }

        // No conversion factor stored: assume 50 g/ml per piece and
        // flag the item as a rough estimate.
        return [($qty * 50) / $baseAmount, 0.5];
    }

    private function bestMatch(User $user, string $phrase): ?\App\Models\Food
    {
        $clean = $this->normalize($phrase);

        if ($clean === '') {
            return null;
        }

        $matches = $this->foodService->search($user, [
            'q' => $clean,
            'scope' => 'all',
            'per_page' => 10,
        ])->getCollection();

        foreach ($matches as $food) {
            if ($this->normalize($food->name) === $clean) {
                return $food;
            }
        }

        foreach ($matches as $food) {
            if (str_starts_with($this->normalize($food->name), $clean)) {
                return $food;
            }
        }

        return $matches->first();
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-zäöüß\s]/u', '', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        // Trailing plural "s" / "en" (German) so "eggs" matches
        // "Egg, whole" and "chicken" matches "Chicken Breast".
        return preg_replace('/(?<=[a-zäöüß])(s|en)$/u', '', $value)
            ?? $value;
    }

    private function normalizeUnit(string $unit): string
    {
        $unit = strtolower($unit);

        if ($unit === '') {
            // No unit given: "<count> <food>" ("2 eggs") counts pieces.
            return 'piece';
        }

        return match (true) {
            in_array($unit, ['g', 'gram', 'grams'], true) => 'g',
            in_array($unit, ['ml', 'milliliter', 'milliliters'], true) => 'ml',
            in_array($unit, ['cup', 'cups'], true) => 'cup',
            in_array($unit, ['slice', 'slices'], true) => 'piece',
            default => 'piece',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function declaredMealType(array $payload): ?string
    {
        $declared = $payload['meal_type'] ?? null;

        return is_string($declared) && $declared !== '' ? $declared : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function guessMealType(string $text, array $payload): ?string
    {
        $declared = $this->declaredMealType($payload);

        if ($declared !== null) {
            return $declared;
        }

        $text = mb_strtolower($text);

        foreach (['breakfast', 'lunch', 'dinner', 'snack'] as $mealType) {
            if (str_contains($text, $mealType)) {
                return $mealType;
            }
        }

        return null;
    }

    private function providerName(): string
    {
        return (string) config(
            'longlivy.integrations.recognition_provider',
            'longlivy-keyword'
        );
    }
}