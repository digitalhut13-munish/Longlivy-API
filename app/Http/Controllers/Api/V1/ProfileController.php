<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {
    }

    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => $request->user()->load('profile'),
            ],
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(
        UpdateProfileRequest $request
    ): JsonResponse {
        $data = $request->validated();

        if ($data === []) {
            return response()->json([
                'success' => false,
                'message' => 'No updatable fields received.',
                'errors' => [
                    'body' => [
                        'Send the payload as JSON with '
                        . 'Content-Type: application/json. Note that '
                        . 'multipart/form-data is not supported for PUT.',
                    ],
                ],
            ], 422);
        }

        $this->profileService->update(
            $request->user(),
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => $request->user()->load('profile'),
            ],
        ]);
    }
}
