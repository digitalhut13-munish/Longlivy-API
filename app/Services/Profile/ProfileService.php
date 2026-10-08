<?php

namespace App\Services\Profile;

use App\Events\ProfileUpdated;
use App\Models\User;
use App\Models\UserProfile;

class ProfileService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): UserProfile
    {
        $profile = $user->profile;

        if ($profile === null) {
            $nameParts = explode(' ', trim((string) $user->name), 2);

            $profile = new UserProfile([
                'user_id' => $user->id,
                'first_name' => $data['first_name']
                    ?? ($nameParts[0] !== '' ? $nameParts[0] : 'user'),
                'last_name' => $data['last_name']
                    ?? ($nameParts[1] ?? ''),
            ]);
        }

        $profile->fill($data);
        $profile->save();

        $user->setRelation('profile', $profile);

        ProfileUpdated::dispatch($user);

        return $profile;
    }

    public function timezoneFor(User $user): string
    {
        return $user->profile?->timezone() ?? 'UTC';
    }
}
