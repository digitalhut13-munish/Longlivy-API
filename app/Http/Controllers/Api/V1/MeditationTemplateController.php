<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Meditation\StoreMeditationTemplateRequest;
use App\Http\Requests\Api\V1\Meditation\UpdateMeditationTemplateRequest;
use App\Http\Resources\MeditationTemplateResource;
use App\Models\MeditationTemplate;
use App\Services\Meditation\MeditationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationTemplateController extends Controller
{
    public function __construct(
        private readonly MeditationTemplateService $templateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $templates = $this->templateService->getUserTemplates(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Templates retrieved successfully.',
            'data' => [
                'templates' => MeditationTemplateResource::collection(
                    $templates
                ),
            ],
        ]);
    }

    public function store(
        StoreMeditationTemplateRequest $request
    ): JsonResponse {
        $template = $this->templateService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Template saved.',
            'data' => [
                'template' => new MeditationTemplateResource($template),
            ],
        ], 201);
    }

    public function update(
        UpdateMeditationTemplateRequest $request,
        MeditationTemplate $template
    ): JsonResponse {
        $this->authorize('update', $template);

        $template = $this->templateService->update(
            $template,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Template saved.',
            'data' => [
                'template' => new MeditationTemplateResource($template),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        MeditationTemplate $template
    ): JsonResponse {
        $this->authorize('delete', $template);

        $this->templateService->delete($template);

        return response()->json([
            'success' => true,
            'message' => 'Template deleted.',
        ]);
    }
}