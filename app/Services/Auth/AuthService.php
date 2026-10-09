<?php

namespace App\Services\Auth;

use App\Mail\AuthCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * How long a password reset code stays valid, in minutes.
     */
    private const RESET_CODE_TTL_MINUTES = 60;

    /**
     * Permanently delete the account and every related record.
     */
    public function deleteAccount(User $user): string
    {
        $deletedAt = now();

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return $deletedAt->toIso8601String();
    }

    /**
     * Send a 6-digit reset code. Responds identically whether the
     * account exists or not, so the endpoint cannot be used to probe
     * registered addresses.
     */
    public function sendPasswordResetCode(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $code = $this->generateCode();

        DB::table('password_reset_tokens')
            ->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

        Mail::to($user->email)
            ->send(new AuthCodeMail($code, 'password_reset'));
    }

    /**
     * Verify a reset code and set a new password. The code is single
     * use and expires after 60 minutes.
     */
    public function resetPassword(string $email, string $code, string $password): void
    {
        $row = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($row === null
            || ! Hash::check($code, (string) $row->token)
            || $this->isCodeExpired($row->created_at)) {
            throw ValidationException::withMessages([
                'code' => 'This reset code is invalid or has expired.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => 'This reset code is invalid or has expired.',
            ]);
        }

        DB::transaction(function () use ($user, $password, $email): void {
            $user->password = Hash::make($password);
            $user->save();

            $user->tokens()->delete();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();
        });
    }

    /**
     * Send a verification code to the signed-in user. Callers must
     * check {@see User::hasVerifiedEmail()} first.
     */
    public function sendVerificationCode(User $user): void
    {
        $code = $this->generateCode();

        $user->emailVerificationCodes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(30),
        ]);

        Mail::to($user->email)
            ->send(new AuthCodeMail($code, 'verification'));
    }

    /**
     * Verify the 6-digit code and mark the email as verified.
     */
    public function verifyEmail(User $user, string $code): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $row = $user->emailVerificationCodes()
            ->latest()
            ->first();

        if ($row === null) {
            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired.',
            ]);
        }

        if ($row->isLockedOut()) {
            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired.',
            ]);
        }

        if ($row->isExpired() || ! Hash::check($code, $row->code_hash)) {
            $row->attempts += 1;
            $row->last_attempt_at = now();
            $row->save();

            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired.',
            ]);
        }

        DB::transaction(function () use ($user, $row): void {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            $row->delete();
        });
    }

    private function isCodeExpired(?Carbon $createdAt): bool
    {
        return $createdAt === null
            || $createdAt->copy()
                ->addMinutes(self::RESET_CODE_TTL_MINUTES)
                ->isPast();
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}