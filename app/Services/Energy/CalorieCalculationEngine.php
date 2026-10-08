<?php

namespace App\Services\Energy;

use App\Models\User;
use App\Models\UserProfile;

/**
 * Individual calorie requirement calculation.
 *
 * The algorithm is selected in config/longlivy.php and every value
 * produced here carries the method and version it was calculated
 * with, so a future algorithm change never invalidates stored
 * values silently.
 */
class CalorieCalculationEngine
{
    /**
     * Unit data required for the calculation. `null` marks a value
     * that is missing from the profile.
     *
     * @return array<string, mixed>
     */
    public function inputs(User $user): array
    {
        $profile = $user->profile;

        return [
            'date_of_birth' => $profile?->date_of_birth?->toDateString(),
            'age' => $this->age($profile),
            'gender' => $profile?->gender,
            'height' => $this->heightCm($profile),
            'height_unit' => 'cm',
            'weight' => $this->weightKg($profile),
            'weight_unit' => 'kg',
            'activity_level' => $profile?->activity_level,
            'body_fat_percentage' => $profile?->body_fat_percentage !== null
                ? (float) $profile->body_fat_percentage
                : null,
        ];
    }

    /**
     * Fields that must be filled in before the engine can produce a
     * value. Missing inputs are reported instead of guessed.
     *
     * @return array<int, string>
     */
    public function missingInputs(User $user): array
    {
        $inputs = $this->inputs($user);
        $missing = [];

        if ($inputs['age'] === null) {
            $missing[] = 'date_of_birth';
        }

        if ($inputs['gender'] === null) {
            $missing[] = 'gender';
        }

        if ($inputs['height'] === null) {
            $missing[] = 'height';
        }

        if ($inputs['weight'] === null) {
            $missing[] = 'current_weight';
        }

        return $missing;
    }

    public function isReady(User $user): bool
    {
        return $this->missingInputs($user) === [];
    }

    /**
     * Basal metabolic rate. Mifflin-St Jeor, kcal/day.
     */
    public function bmr(User $user): ?float
    {
        if (! $this->isReady($user)) {
            return null;
        }

        $inputs = $this->inputs($user);

        $weight = (float) $inputs['weight'];
        $height = (float) $inputs['height'];
        $age = (float) $inputs['age'];

        $base = (10 * $weight) + (6.25 * $height) - (5 * $age);

        $bmr = $this->isFemale($inputs['gender'])
            ? $base - 161
            : $base + 5;

        return round($bmr, 2);
    }

    /**
     * Multiplier from the configured activity level.
     */
    public function activityFactor(User $user): float
    {
        $level = $user->profile?->activity_level ?? 'moderate';

        $factors = config('longlivy.calorie.activity_factors');

        return (float) ($factors[$level] ?? $factors['moderate']);
    }

    /**
     * Everyday activity above the basal rate.
     *
     * The activity level already contains an average everyday
     * movement, so only the part above the basal rate is stored as a
     * separate component. Sporting and imported activities are
     * credited on top and never folded into this factor - that is
     * what keeps the balance free of double counting.
     */
    public function everydayActivity(User $user): ?float
    {
        $bmr = $this->bmr($user);

        if ($bmr === null) {
            return null;
        }

        $factor = $this->activityFactor($user);

        return round($bmr * ($factor - 1), 2);
    }

    /**
     * Estimated total energy consumption without dedicated sport or
     * imported activities: basal rate plus everyday activity.
     */
    public function tdee(User $user): ?float
    {
        $bmr = $this->bmr($user);

        if ($bmr === null) {
            return null;
        }

        return round($bmr * $this->activityFactor($user), 2);
    }

    /**
     * Method and version used for the current configuration.
     *
     * @return array<string, string>
     */
    public function method(): array
    {
        return [
            'method' => config('longlivy.calorie.method'),
            'version' => (string) config('longlivy.calorie.version'),
        ];
    }

    /**
     * Automatic macronutrient targets for a daily calorie target.
     *
     * @return array<string, float>
     */
    public function macroTargets(float $calories): array
    {
        $ratios = config('longlivy.nutrition.macro_ratios');

        $protein = ($calories * (float) $ratios['protein']) / 4;
        $carbohydrates = ($calories * (float) $ratios['carbohydrates']) / 4;
        $fat = ($calories * (float) $ratios['fat']) / 9;

        return [
            'protein' => round($protein, 2),
            'carbohydrates' => round($carbohydrates, 2),
            'fat' => round($fat, 2),
            'fiber' => round(
                (float) config('longlivy.nutrition.fiber_target'),
                2
            ),
        ];
    }

    private function age(?UserProfile $profile): ?int
    {
        if ($profile === null || $profile->date_of_birth === null) {
            return null;
        }

        return $profile->date_of_birth->age;
    }

    private function heightCm(?UserProfile $profile): ?float
    {
        if ($profile === null || $profile->height === null) {
            return null;
        }

        $height = (float) $profile->height;

        if ($profile->height_unit === 'in') {
            return round($height * 2.54, 2);
        }

        return $height;
    }

    private function weightKg(?UserProfile $profile): ?float
    {
        if ($profile === null || $profile->current_weight === null) {
            return null;
        }

        $weight = (float) $profile->current_weight;

        if ($profile->weight_unit === 'lb') {
            return round($weight * 0.45359237, 2);
        }

        return $weight;
    }

    private function isFemale(mixed $gender): bool
    {
        if (! is_string($gender)) {
            return false;
        }

        return in_array(strtolower($gender), ['female', 'woman', 'f'], true);
    }
}
