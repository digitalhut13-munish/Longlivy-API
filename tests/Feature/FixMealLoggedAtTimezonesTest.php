<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixMealLoggedAtTimezonesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_before_cutoff(): void
    {
        $this->artisan('longlivy:fix-meal-timezones')
            ->assertExitCode(1);
    }

    public function test_subtracts_the_profile_offset_and_recomputes_date(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'timezone' => 'Asia/Kolkata',
        ]);

        // A meal saved the old way: local 15:50 IST stored as 15:50 UTC.
        $meal = Meal::create([
            'user_id' => $user->id,
            'meal_type' => 'lunch',
            'logged_at' => '2026-01-15 15:50:00',
            'date' => '2026-01-15',
            'source' => 'manual',
        ]);

        $this->artisan('longlivy:fix-meal-timezones', [
            '--before' => now()->addDay()->toDateTimeString(),
        ])->assertExitCode(0);

        $meal->refresh();

        // 15:50 - 5:30 = 10:20 UTC, still 15:50 IST the same day.
        $this->assertSame(
            '2026-01-15 10:20:00',
            $meal->logged_at->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2026-01-15',
            $meal->date->toDateString()
        );
    }

    public function test_dry_run_changes_nothing(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'timezone' => 'Asia/Kolkata',
        ]);

        $meal = Meal::create([
            'user_id' => $user->id,
            'meal_type' => 'lunch',
            'logged_at' => '2026-01-15 15:50:00',
            'date' => '2026-01-15',
            'source' => 'manual',
        ]);

        $this->artisan('longlivy:fix-meal-timezones', [
            '--before' => now()->addDay()->toDateTimeString(),
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(
            '2026-01-15 15:50:00',
            $meal->refresh()->logged_at->format('Y-m-d H:i:s')
        );
    }

    public function test_skips_rows_after_the_cutoff(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'timezone' => 'Asia/Kolkata',
        ]);

        $meal = Meal::create([
            'user_id' => $user->id,
            'meal_type' => 'dinner',
            'logged_at' => '2026-10-19 21:00:00',
            'date' => '2026-10-19',
            'source' => 'manual',
        ]);

        $this->artisan('longlivy:fix-meal-timezones', [
            '--before' => $meal->created_at->subDay()->toDateTimeString(),
        ])->assertExitCode(0);

        $this->assertSame(
            '2026-10-19 21:00:00',
            $meal->refresh()->logged_at->format('Y-m-d H:i:s')
        );
    }

    public function test_date_crosses_day_boundary_for_early_morning_meals(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'timezone' => 'Asia/Kolkata',
        ]);

        // Local 00:30 IST stored as 00:30 UTC → true UTC is 19:00 the
        // previous day; the calendar date in IST stays 2026-01-15.
        $meal = Meal::create([
            'user_id' => $user->id,
            'meal_type' => 'breakfast',
            'logged_at' => '2026-01-15 00:30:00',
            'date' => '2026-01-15',
            'source' => 'manual',
        ]);

        $this->artisan('longlivy:fix-meal-timezones', [
            '--before' => now()->addDay()->toDateTimeString(),
        ])->assertExitCode(0);

        $meal->refresh();

        $this->assertSame(
            '2026-01-14 19:00:00',
            $meal->logged_at->format('Y-m-d H:i:s')
        );
        $this->assertSame('2026-01-15', $meal->date->toDateString());
    }
}
