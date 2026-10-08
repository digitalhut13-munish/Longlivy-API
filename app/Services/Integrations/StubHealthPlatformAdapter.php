<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\HealthPlatformAdapter;
use App\Models\User;

class StubHealthPlatformAdapter implements HealthPlatformAdapter
{
    public function __construct(
        private readonly string $providerName = 'stub'
    ) {
    }

    public function name(): string
    {
        return $this->providerName;
    }

    public function supports(): bool
    {
        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(User $user, string $type, string $since): array
    {
        return [];
    }

    public function disconnect(User $user): void
    {
        // Nothing was ever connected.
    }
}
