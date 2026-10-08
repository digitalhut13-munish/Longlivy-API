<?php

namespace App\Contracts\Integrations;

interface BarcodeLookupService
{
    /**
     * Resolve a barcode to a product payload.
     *
     * Return null when the code is unknown so the client can fall back
     * to manual entry. Never invent product data.
     *
     * @return array{
     *     barcode: string,
     *     name: string|null,
     *     brand: string|null,
     *     serving_size: float|null,
     *     unit: string|null,
     *     calories: float|null,
     *     protein: float|null,
     *     carbohydrates: float|null,
     *     fat: float|null,
     *     fiber: float|null,
     *     sugar: float|null,
     *     sodium: float|null,
     *     source: string
     * }|null
     */
    public function lookup(string $barcode): ?array;
}
