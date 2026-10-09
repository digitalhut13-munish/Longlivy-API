<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\DeleteAccountRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;
use App\Models\Device;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {

            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            $user->profile()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'height' => $request->height,
                'height_unit' => $request->height_unit ?? 'cm',
                'current_weight' => $request->current_weight,
                'weight_unit' => $request->weight_unit ?? 'kg',
                'address' => $request->address,
                'timezone' => $request->timezone ?? 'UTC',
                'activity_level' => $request->activity_level ?? 'moderate',
            ]);

            return $user;
        });

        $token = $user->createToken(
            'longlivy-mobile',
            ['*'],
            now()->addDays((int) config('longlivy.auth.token_ttl_days'))
        );

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => $user->load('profile'),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at
                    ?->toISOString(),
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $user->createToken(
            'longlivy-mobile',
            ['*'],
            now()->addDays((int) config('longlivy.auth.token_ttl_days'))
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user->load('profile'),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at
                    ?->toISOString(),
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user()->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();
        $token = $user->currentAccessToken();

        if ($token !== null) {
            Device::where('user_id', $user->id)
                ->delete();
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * Permanently delete the account (GDPR / store guideline).
     */
    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        if ($request->validated()['confirmation'] !== 'DELETE') {
            return response()->json([
                'success' => false,
                'message' => 'Type DELETE to confirm.',
                'errors' => [
                    'confirmation' => [
                        'Type DELETE to confirm.',
                    ],
                ],
            ], 422);
        }

        $deletedAt = $this->authService->deleteAccount(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Account deleted.',
            'data' => [
                'deleted_at' => $deletedAt,
            ],
        ]);
    }

    /**
     * Start the password reset flow. The response is identical for
     * known and unknown addresses.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetCode($request->email);

        return response()->json([
            'success' => true,
            'message' => 'If an account exists for this email, a reset code has been sent.',
        ]);
    }

    /**
     * Finish the password reset flow with the 6-digit code.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->email,
            $request->code,
            $request->password
        );

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset. Please sign in.',
        ]);
    }

    /**
     * Resend the email verification code.
     */
    public function resendVerificationCode(): JsonResponse
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified.',
            ], 409);
        }

        $this->authService->sendVerificationCode($user);

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent.',
        ]);
    }

    /**
     * Confirm the emailed verification code.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = auth()->user();

        $this->authService->verifyEmail($user, $request->code);

        return response()->json([
            'success' => true,
            'message' => 'Email verified.',
            'data' => [
                'user' => $user->fresh()->load('profile'),
            ],
        ]);
    }
}