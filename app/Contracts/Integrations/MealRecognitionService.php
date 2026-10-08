<?php

namespace App\Contracts\Integrations;

interface MealRecognitionService
{
    /**
     * Turn an image, voice transcript or free-text description into a
     * structured meal draft.
     *
     * The returned entries are ALWAYS a proposal: every entry carries
     * needs_review = true and must be confirmed by the user before it
     * may be persisted.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     meal_type: string|null,
     *     items: array<int, array<string, mixed>>,
     *     confidence: float,
     *     needs_review: true,
     *     provider: string
     * }
     */
    public function recognize(string $type, array $payload): array;
}
