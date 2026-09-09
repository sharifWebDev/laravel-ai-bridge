<?php

namespace Sharifuddin\LaravelAiBridge\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Sharifuddin\LaravelAiBridge\Contracts\AiProcessorInterface;

class AiController extends Controller
{
    protected AiProcessorInterface $aiService;

    public function __construct(AiProcessorInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    // ওয়েব ভিউ রেন্ডার করার জন্য
    public function index(Request $request)
    {
        return view('ai-bridge::chat'); // প্যাকেজ ভিউ অথবা মেসেজ রিটার্ন করতে পারেন
    }

    // এপিআই প্রম্পট হ্যান্ডেল করার জন্য
    public function handle(Request $request)
    {
        $userPrompt = $request->input('prompt');
        $aiData = $this->aiService->processPrompt($userPrompt);

        if (isset($aiData['unauthorized']) && $aiData['unauthorized']) {
            return response()->json([
                'status' => 'error',
                'message' => $aiData['message']
            ], 403);
        }

        $functionCall = $aiData['candidates'][0]['content']['parts'][0]['functionCall'] ?? null;

        if ($functionCall) {
            return response()->json([
                'status' => 'success',
                'action' => $functionCall['name'],
                'arguments' => $functionCall['args'] ?? []
            ]);
        }

        $textResponse = $aiData['candidates'][0]['content']['parts'][0]['text'] ?? "No response.";
        return response()->json(['message' => $textResponse]);
    }
}
