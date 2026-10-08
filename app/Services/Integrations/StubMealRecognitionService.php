<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\MealRecognitionService;

class StubMealRecognitionService implements MealRecognitionService
{
    /**
     * Local, dependency-free draft builder. It only echoes back what it
     * can parse from a free-text description so the review flow can be
     * exercised end to end before a real model is connected.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function recognize(string $type, array $payload): array
    {
        $text = trim((string) ($payload['text'] ?? ''));

        return [
            'meal_type' => $this->guessMealType($payload),
            'items' => $this->parseText($text),
            'confidence' => $text === '' ? 0.0 : 0.4,
            'needs_review' => true,
            'provider' => 'stub',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function guessMealType(array $payload): ?string
    {
        $declared = $payload['meal_type'] ?? null;

        if (is_string($declared) && $declared !== '') {
            return $declared;
        }

        $text = strtolower((string) ($payload['text'] ?? ''));

        foreach (['breakfast', 'lunch', 'dinner', 'snack'] as $mealType) {
            if (str_contains($text, $mealType)) {
                return $mealType;
            }
        }

        return null;
    }

    /**
     * Parses simple "<amount><unit> <food>" segments, e.g.
     * "300 grams of chicken with 150 grams of rice".
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $pattern = '/(\d+(?:[.,]\d+)?)\s*(grams?|g|milliliters?|ml|pieces?|pcs)\s+(?:of\s+)?([a-zäöüß\s]+?)(?=\s+with\s+|\s+and\s+|\s*,\s*|$)/iu';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $items = [];

        foreach ($matches as $match) {
            $items[] = [
                'name' => trim($match[3]),
                'quantity' => (float) str_replace(',', '.', $match[1]),
                'unit' => $this->normalizeUnit($match[2]),
                'calories' => null,
                'protein' => null,
                'carbohydrates' => null,
                'fat' => null,
                'estimated' => true,
                'needs_review' => true,
            ];
        }

        return $items;
    }

    private function normalizeUnit(string $unit): string
    {
        $unit = strtolower($unit);

        return match (true) {
            in_array($unit, ['g', 'grams'], true) => 'g',
            in_array($unit, ['ml', 'milliliters'], true) => 'ml',
            default => 'piece',
        };
    }
}
