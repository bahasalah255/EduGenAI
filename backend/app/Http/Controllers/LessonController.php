<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use Throwable;

class LessonController extends Controller
{
    public function generate(Request $request, AIService $aiService)
    {
        $request->validate([
            'topic' => 'required|string|max:120'
        ]);

        try {
            $result = $aiService->generate($request->topic);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'The lesson generator is taking too long to respond right now. Please try again in a moment.',
            ], 503);
        }

        return response()->json($result);
    }
}