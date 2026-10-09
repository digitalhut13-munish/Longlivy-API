<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Device\RegisterDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Register (or refresh) a push token. The token is the unique key,
     * so a reinstall just overwrites the existing row.
     */
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['last_seen_at'] = now();

        $device = Device::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $data['token']],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered.',
            'data' => [
                'device' => new DeviceResource($device),
            ],
        ]);
    }

    /**
     * Remove a push token (called on logout).
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        Device::where('user_id', $request->user()->id)
            ->where('token', $token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device removed.',
        ]);
    }
}