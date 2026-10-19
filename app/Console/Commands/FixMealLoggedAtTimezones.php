<?php

namespace App\Console\Commands;

use App\Models\Meal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-off data repair for meals saved before the logged_at timezone
 * change was deployed. Those rows hold the client's local wall time
 * labelled as UTC (e.g. a 15:50 IST meal stored as 15:50+00:00), so
 * they display hours late for anyone outside UTC.
 *
 * The correction subtracts the profile timezone's UTC offset (at that
 * instant, so DST is handled) from logged_at and recomputes the
 * calendar date in the profile timezone.
 *
 * Always run with --dry-run first, then re-run without it.
 */
class FixMealLoggedAtTimezones extends Command
{
    protected $signature = 'longlivy:fix-meal-timezones
        {--before= : Only touch meals created before this UTC datetime (required, e.g. "2026-10-19 12:00:00")}
        {--dry-run : Show what would change without writing}';

    protected $description = 'One-off fix: subtract the profile timezone offset from meals.logged_at rows created before the timezone change was deployed.';

    public function handle(): int
    {
        $before = (string) $this->option('before');

        if ($before === '') {
            $this->error(
                'Pass --before="YYYY-MM-DD HH:MM:SS" (UTC) with the '
                .'moment the timezone fix was deployed. Only meals '
                .'created before that instant are corrected.'
            );

            return self::FAILURE;
        }

        try {
            $cutoff = Carbon::parse($before)->utc();
        } catch (\Throwable) {
            $this->error("Could not parse [--before={$before}] as a datetime.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(
            ($dryRun ? '[dry-run] Correcting' : 'Correcting')
            ." meals created before {$cutoff->toIso8601String()}..."
        );

        $fixed = 0;
        $skipped = 0;
        $dateChanged = 0;

        Meal::query()
            ->where('created_at', '<', $cutoff)
            ->with('user.profile')
            ->chunkById(200, function ($meals) use (
                &$fixed,
                &$skipped,
                &$dateChanged,
                $dryRun
            ) {
                foreach ($meals as $meal) {
                    $profile = $meal->user?->profile;

                    if ($profile === null) {
                        $skipped++;

                        continue;
                    }

                    $timezone = $profile->timezone();

                    // The stored naive value is the client's local
                    // wall time; recover its UTC offset by reading it
                    // in the profile timezone.
                    $naive = $meal->logged_at->copy();
                    $local = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $naive->format('Y-m-d H:i:s'),
                        $timezone
                    );

                    $offsetMinutes = $local->offsetMinutes;

                    if ($offsetMinutes === 0) {
                        $skipped++;

                        continue;
                    }

                    $corrected = $naive->copy()->subMinutes($offsetMinutes);
                    $correctedDate = $corrected
                        ->copy()
                        ->setTimezone($timezone)
                        ->toDateString();

                    $previousDate = $meal->date?->toDateString();

                    if (! $dryRun) {
                        $meal->logged_at = $corrected;
                        $meal->date = $correctedDate;
                        $meal->save();
                    }

                    $fixed++;

                    if ($previousDate !== $correctedDate) {
                        $dateChanged++;
                    }

                    $this->line(
                        sprintf(
                            '#%d user=%d %s -> %s (date %s, offset %s)',
                            $meal->id,
                            $meal->user_id,
                            $naive->toIso8601String(),
                            $corrected->toIso8601String(),
                            $correctedDate,
                            $local->format('P')
                        )
                    );
                }
            });

        $this->info(
            sprintf(
                '%d meal(s) %s, %d skipped (no profile or UTC offset 0), '
                .'%d date change(s).',
                $fixed,
                $dryRun ? 'would be corrected' : 'corrected',
                $skipped,
                $dateChanged
            )
        );

        return self::SUCCESS;
    }
}
