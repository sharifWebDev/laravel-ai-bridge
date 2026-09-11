<?php

namespace Sharifuddin\LaravelAiBridge\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Sharifuddin\LaravelAiBridge\Services\AiService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AiController extends Controller
{
    public function __construct(protected AiService $aiService)
    {
    }

    /**
     * Render the Copilot-style Chat UI view.
     */
    public function index(Request $request): View
    {
        return view('ai-bridge::chat');
    }

    /**
     * Handle an AI prompt request via the hybrid retrieval + secure
     * execution pipeline (Sharifuddin\LaravelAiBridge\Services\AiService::chat()).
     *
     * Response shape is preserved from the previous implementation for
     * backward compatibility: {status, message} or {status, action, result}.
     */
    public function handle(Request $request): mixed
    {
        $request->validate([
            'prompt' => 'required|string|max:5000',
            'export' => 'sometimes|boolean',
        ]);
        // $request->merge(['format' => 'excel', 'export' => 'excel', 'grouped' => true]);

        $userPrompt = (string) $request->input('prompt');
        $aiData = $this->aiService->chat($userPrompt);
        // $userData = app(\App\Http\Controllers\Api\UserController::class); 
        // $aiData = $userData->index($request);

        // Excel/File download response হলে সরাসরি return
        if ($aiData instanceof BinaryFileResponse) {
            return $aiData;
        }


        // $aiData = $aiData->getData(true); 

        if (($aiData['status'] ?? null) === 'error') {
            $message = $aiData['message'] ?? 'An error occurred while communicating with the AI service.';
            $lower = strtolower($message);

            if (str_contains($lower, 'login') || str_contains($lower, 'authenticated') || str_contains($lower, 'unauthenticated')) {
                $status = 401;
            } elseif (str_contains($lower, 'permission') || str_contains($lower, 'authorized') || str_contains($lower, 'role')) {
                $status = 403;
            } else {
                $status = 422;
            }

            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], $status);
        }

       
        return response()->json($aiData);
    }
}
