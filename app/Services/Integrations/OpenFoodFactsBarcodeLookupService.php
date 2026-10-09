<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\BarcodeLookupService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Product lookup against Open Food Facts.
 *
 * The response is normalised to per 100 g/ml values (OFF's nutriments
 * are already delivered per 100 g) so the returned payload can be
 * cached directly as a catalog food.
 */
class OpenFoodFactsBarcodeLookupService implements BarcodeLookupService
{
    public function lookup(string $barcode): ?array
    {
        // Tests never touch the network. Production failures degrade
        // to "unknown barcode" so the client can fall back to manual
        // entry instead of failing.
        if (app()->environment('testing')) {
            return null;
        }

        try {
            $response = Http::timeout(
                (int) config('longlivy.integrations.barcode_timeout', 5)
            )->acceptJson()->get(
                rtrim(
                    (string) config(
                        'longlivy.integrations.barcode_base_url',
                        'https://world.openfoodfacts.org'
                    ),
                    '/'
                ).'/api/v2/product/'.$barcode.'.json',
                ['fields' => 'product_name,brands,quantity,nutriments']
            );
        } catch (\Throwable $exception) {
            Log::warning('Open Food Facts lookup failed.', [
                'barcode' => $barcode,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $product = (array) ($response->json('product') ?? []);

        if ($product === []) {
            return null;
        }

        $nutriments = (array) ($product['nutriments'] ?? []);

        return [
            'barcode' => $barcode,
            'name' => isset($product['product_name'])
                ? (string) $product['product_name']
                : null,
            'brand' => isset($product['brands'])
                ? (string) $product['brands']
                : null,
            'serving_size' => isset($nutriments['serving_quantity'])
                ? (float) $nutriments['serving_quantity']
                : null,
            'unit' => $this->baseUnit((string) ($product['quantity'] ?? '')),
            'calories' => $this->float($nutriments['energy-kcal_100g'] ?? null),
            'protein' => $this->float($nutriments['proteins_100g'] ?? null),
            'carbohydrates' => $this->float(
                $nutriments['carbohydrates_100g'] ?? null
            ),
            'fat' => $this->float($nutriments['fat_100g'] ?? null),
            'fiber' => $this->float($nutriments['fiber_100g'] ?? null),
            'sugar' => $this->float($nutriments['sugars_100g'] ?? null),
            'sodium' => $this->float($nutriments['sodium_100g'] ?? null),
            'source' => 'open_food_facts',
        ];
    }

    private function baseUnit(string $quantity): string
    {
        return preg_match('/(?:ml|l(?:it(?:er|re))?s?)/i', $quantity) === 1
            ? 'ml'
            : 'g';
    }

    private function float(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}