<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;
use Illuminate\Support\Facades\Log;
use ArPHP\I18N\Arabic;

class LessonController extends Controller
{
    /**
     * Returns true if a string contains Arabic characters.
     */
    private function containsArabic(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }

    /**
     * Reshape a single Arabic string so DomPDF can render it correctly.
     * Passes non-Arabic strings through unchanged.
     */
    private function reshapeArabicString(string $text, Arabic $arabic): string
    {
        if (trim($text) === '' || !$this->containsArabic($text)) {
            return $text;
        }

        try {
            return $arabic->utf8Glyphs($text);
        } catch (Throwable $e) {
            Log::warning('Arabic reshape failed: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Recursively walk every string in the payload and reshape Arabic text.
     */
    private function reshapeArabicPayload(array $data, Arabic $arabic): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->reshapeArabicString($value, $arabic);
            } elseif (is_array($value)) {
                $data[$key] = $this->reshapeArabicPayload($value, $arabic);
            }
        }
        return $data;
    }

    public function exportPdf(Request $request)
    {
        $payload = $request->validate([
            'topic' => 'required|string|max:120',
            'title' => 'nullable|string|max:180',
            'level' => 'nullable|string|max:20',
            'lesson' => 'nullable|string',
            'grammar_points' => 'nullable|array',
            'grammar_points.*' => 'nullable|string',
            'vocabulary' => 'nullable|array',
            'vocabulary.*.german' => 'nullable|string',
            'vocabulary.*.arabic' => 'nullable|string',
            'vocabulary.*.example' => 'nullable|string',
            'vocabulary.*.pronunciation' => 'nullable|string',
            'examples' => 'nullable|array',
            'examples.*' => 'nullable|string',
            'sentences' => 'nullable|array',
            'sentences.*' => 'nullable|string',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string',
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string',
            'exercise_groups' => 'nullable|array',
            'exercise_groups.*.title' => 'nullable|string',
            'exercise_groups.*.type' => 'nullable|string',
            'exercise_groups.*.instructions' => 'nullable|string',
            'exercise_groups.*.questions' => 'nullable|array',
            'exercise_groups.*.questions.*' => 'nullable|string',
            'exercise_groups.*.answers' => 'nullable|array',
            'exercise_groups.*.answers.*' => 'nullable|string',
            'exercise_groups.*.solutions_arabic' => 'nullable|array',
            'exercise_groups.*.solutions_arabic.*' => 'nullable|string',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'nullable|string',
            'common_mistakes' => 'nullable|array',
            'common_mistakes.*.mistake' => 'nullable|string',
            'common_mistakes.*.correction' => 'nullable|string',
            'common_mistakes.*.explanation_arabic' => 'nullable|string',
            'usage_notes' => 'nullable|array',
            'usage_notes.*' => 'nullable|string',
            'grammar_tips' => 'nullable|array',
            'grammar_tips.*' => 'nullable|string',
            'memory_tricks' => 'nullable|array',
            'memory_tricks.*' => 'nullable|string',
            'summary' => 'nullable|string',
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

        if (empty($payload['title'])) {
            $payload['title'] = $payload['topic'];
        }

        if (empty($payload['level'])) {
            $payload['level'] = 'A2-B1';
        }

        // ─── Arabic Text Reshaping ──────────────────────────────────────────────
        // DomPDF cannot natively shape Arabic characters (connecting letters).
        // The ArPHP library reshapes each Arabic string into properly connected
        // glyph form before it reaches the PDF renderer.
        try {
            $arabic = new Arabic();
            $payload = $this->reshapeArabicPayload($payload, $arabic);
        } catch (Throwable $e) {
            Log::warning('Arabic reshaping step failed, continuing without it: ' . $e->getMessage());
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