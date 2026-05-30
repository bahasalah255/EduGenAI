<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function exportPdf(Request $request)
    {
        $payload = $request->validate([
            'topic' => 'required|string|max:120',
            'lesson' => 'nullable|string',
            'sentences' => 'nullable|array',
            'sentences.*' => 'nullable|string',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string',
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string',
            'exercise_groups' => 'nullable|array',
            'exercise_groups.*.group_title' => 'nullable|string',
            'exercise_groups.*.exercises' => 'nullable|array',
            'exercise_groups.*.exercises.*' => 'nullable|string',
            'exercise_groups.*.solutions_arabic' => 'nullable|array',
            'exercise_groups.*.solutions_arabic.*' => 'nullable|string',
        ]);

        // If the lesson field contains embedded JSON (string), attempt to extract and merge it
        if (!empty($payload['lesson']) && is_string($payload['lesson'])) {
            $decoded = json_decode($payload['lesson'], true);
            if (is_array($decoded)) {
                $payload = array_merge($payload, $decoded);
            } else {
                // try to extract a first JSON object from the string (balanced braces)
                $text = $payload['lesson'];
                $start = strpos($text, '{');
                if ($start !== false) {
                    $depth = 0;
                    $len = strlen($text);
                    for ($i = $start; $i < $len; $i++) {
                        if ($text[$i] === '{') $depth++;
                        if ($text[$i] === '}') $depth--;
                        if ($depth === 0) {
                            $candidate = substr($text, $start, $i - $start + 1);
                            $maybe = json_decode($candidate, true);
                            if (is_array($maybe)) {
                                $payload = array_merge($payload, $maybe);
                                break;
                            }
                        }
                    }
                }
            }
        }

        try {
            $pdf = Pdf::loadView('pdf.lesson', [
                'lesson' => $payload,
            ])->setPaper('a4', 'portrait');

            $filename = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $payload['topic'] ?? 'lesson') . '.pdf';

            return $pdf->download($filename);
        } catch (Throwable $e) {
            Log::error('Export PDF failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Failed to render PDF. See server logs for details.'
            ], 500);
        }
    }

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