<?php

namespace App\Contracts\Integrations;

use App\Models\User;

interface HealthPlatformAdapter
{
    /**
     * Machine identifier of the provider, e.g. "apple_health".
     */
    public function name(): string;

    /**
     * Whether this adapter can talk to a live provider right now.
     */
    public function supports(): bool;

    /**
     * Fetch normalized records for a user since a given timestamp.
     *
     * Records must already be normalized to the Longlivy shape and must
     * carry their external id so duplicates can be rejected.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(User $user, string $type, string $since): array;

    /**
     * Remove the stored connection for a user.
     */
    public function disconnect(User $user): void;
}
