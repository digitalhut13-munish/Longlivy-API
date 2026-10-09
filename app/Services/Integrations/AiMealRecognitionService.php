<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\MealRecognitionService;
use App\Models\User;
use App\Services\Nutrition\FoodService;

/**
 * Local, provider-light recognition for text and voice drafts.
 *
 * Free-text descriptions are parsed into "<amount> <unit> <food>"
 * segments and each segment is matched against the food database
 * (shared catalog plus the user's own foods) so the draft carries
 * food_id + nutrient snapshots ready for review. Add a vision/LLM
 * provider behind this contract once one is contracted.
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
     * @return array<int, array{qty: float, unit: string, name: string}>
     */
    private function parseSegments(string $text): array
    {
        $pattern = '/(\d+(?:[.,]\d+)?)\s*(?:of\s+)?'
            .'(grams?|g|milliliters?|ml|pieces?|pcs|slices?|cups?)\s+'
            .'(?:of\s+)?([a-zäöüß\s]+?)'
            .'(?=\s+(?:for|with|and|plus)\b|\s*,\s*|$)/iu';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $segments = [];

        foreach ($matches as $match) {
            $segments[] = [
                'qty' => (float) str_replace(',', '.', $match[1]),
                'unit' => $this->normalizeUnit($match[2]),
                'name' => trim($match[3]),
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
                'name' => $segment['name'],
                'quantity' => $segment['qty'],
                'unit' => $segment['unit'],
                'nutrients' => null,
                'confidence' => 0.3,
                'estimated' => true,
            ];
        }

        return [
            'food_id' => $food->id,
            'name' => $food->name,
            'quantity' => $segment['qty'],
            'unit' => $segment['unit'],
            'nutrients' => [
                'calories' => (float) $food->calories,
                'protein' => (float) $food->protein,
                'carbohydrates' => (float) $food->carbohydrates,
                'fat' => (float) $food->fat,
            ],
            'confidence' => 0.82,
            'estimated' => true,
        ];
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

        // Trailing plural "s" / "en" (German) so "egg" matches "eggs".
        return preg_replace('/\b(s|en)$/u', '', $value) ?? $value;
    }

    private function normalizeUnit(string $unit): string
    {
        $unit = strtolower($unit);

        return match (true) {
            in_array($unit, ['g', 'grams'], true) => 'g',
            in_array($unit, ['ml', 'milliliters'], true) => 'ml',
            in_array($unit, ['cups', 'cup'], true) => 'cup',
            in_array($unit, ['slices', 'slice'], true) => 'piece',
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