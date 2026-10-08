<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\BarcodeLookupService;

class StubBarcodeLookupService implements BarcodeLookupService
{
    /**
     * No external product database is wired up yet, so every code is
     * reported as unknown and the client switches to manual entry.
     */
    public function lookup(string $barcode): ?array
    {
        return null;
    }
}
