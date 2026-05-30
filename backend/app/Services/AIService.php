<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class AIService
{
    private function tryParseJsonString(string $value): ?array
    {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $sanitized = trim($value);
        $sanitized = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"], ['"', '"', "'", "'"], $sanitized);
        $sanitized = preg_replace("/'([^']*)'/", '"$1"', $sanitized);
        $sanitized = preg_replace('/,\s*([}\]])/', '$1', $sanitized);

        $decoded = json_decode($sanitized, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function extractFirstJsonObject(string $text): ?array
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($char === '{') {
                $depth++;
            }

            if ($char === '}') {
                $depth--;
            }

            if ($depth === 0) {
                $candidate = substr($text, $start, $i - $start + 1);
                $parsed = $this->tryParseJsonString($candidate);

                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    private function extractJsonArrayByKey(string $text, string $key): array
    {
        $position = strpos($text, '"' . $key . '"');
        if ($position === false) {
            $position = strpos($text, "'" . $key . "'");
        }
        if ($position === false) {
            $position = strpos($text, $key);
        }
        if ($position === false) {
            return [];
        }

        $bracketStart = strpos($text, '[', $position);
        if ($bracketStart === false) {
            return [];
        }

        $depth = 0;
        $endIndex = null;
        $length = strlen($text);
        for ($i = $bracketStart; $i < $length; $i++) {
            $char = $text[$i];
            if ($char === '[') {
                $depth++;
            } elseif ($char === ']') {
                $depth--;
                if ($depth === 0) {
                    $endIndex = $i;
                    break;
                }
            }
        }

        $snippet = $endIndex !== null
            ? substr($text, $bracketStart, $endIndex - $bracketStart + 1)
            : (function () use ($text, $bracketStart) {
                $tail = substr($text, $bracketStart);
                if (preg_match('/\n\s*["\']?[A-Za-z0-9_\- ]+["\']?\s*:\s*/', $tail, $match, PREG_OFFSET_CAPTURE, 1)) {
                    $cut = $match[0][1];
                    return substr($tail, 0, $cut) . ']';
                }

                return $tail . ']';
            })();

        $parsed = $this->tryParseJsonString($snippet);
        if (is_array($parsed)) {
            return array_values(array_unique(array_filter(array_map(function ($item) {
                return is_string($item) ? trim(str_replace('\\n', "\n", $item)) : '';
            }, $parsed))));
        }

        preg_match_all('/["\']([^"\']+)["\']/', $snippet, $matches);
        if (!empty($matches[1])) {
            return array_values(array_unique(array_filter(array_map(function ($item) {
                return trim(str_replace('\\n', "\n", $item));
            }, $matches[1]))));
        }

        return [];
    }

    private function normalizeLessonPayload(array $payload): array
    {
        $normalized = $payload;

        if (!empty($normalized['lesson']) && is_string($normalized['lesson'])) {
            $lessonText = $normalized['lesson'];

            $parsedLesson = $this->tryParseJsonString($lessonText);
            if (is_array($parsedLesson)) {
                $normalized = array_merge($normalized, $parsedLesson);
                $lessonText = is_string($normalized['lesson'] ?? null) ? $normalized['lesson'] : $lessonText;
            } else {
                $unescaped = str_replace(["\\n", '\\"', "\\'"], ["\n", '"', "'"], $lessonText);
                $embedded = $this->extractFirstJsonObject($unescaped);
                if (is_array($embedded)) {
                    $normalized = array_merge($normalized, $embedded);
                    $lessonText = is_string($normalized['lesson'] ?? null) ? $normalized['lesson'] : $lessonText;
                }
            }

            if (is_string($lessonText) && $lessonText !== '') {
                $embeddedLesson = $this->extractFirstJsonObject($lessonText);
                if (is_array($embeddedLesson)) {
                    $normalized = array_merge($normalized, $embeddedLesson);
                }
            }
        }

        $lessonText = is_string($normalized['lesson'] ?? null) ? $normalized['lesson'] : '';

        foreach (['sentences', 'exercises', 'solutions_arabic', 'questions', 'answers'] as $key) {
            if (empty($normalized[$key]) && $lessonText !== '') {
                $normalized[$key] = $this->extractJsonArrayByKey($lessonText, $key);
            }
        }

        if (empty($normalized['solutions_arabic']) && $lessonText !== '') {
            $normalized['solutions_arabic'] = $this->extractJsonArrayByKey($lessonText, 'solutions');
        }

        $normalized['topic'] = trim((string) ($normalized['topic'] ?? $payload['topic'] ?? ''));
        $normalized['lesson'] = trim((string) ($normalized['lesson'] ?? ''));
        $normalized['sentences'] = array_values(array_filter(array_map('trim', (array) ($normalized['sentences'] ?? []))));
        $normalized['exercises'] = array_values(array_filter(array_map('trim', (array) ($normalized['exercises'] ?? []))));
        $normalized['solutions_arabic'] = array_values(array_filter(array_map('trim', (array) ($normalized['solutions_arabic'] ?? []))));
        $normalized['questions'] = array_values(array_filter(array_map('trim', (array) ($normalized['questions'] ?? []))));
        $normalized['answers'] = array_values(array_filter(array_map('trim', (array) ($normalized['answers'] ?? []))));

        return $normalized;
    }

    public function generate($topic)
    {
                $prompt = <<<'PROMPT'
You are an expert German teacher and educational content creator.

The user will give you ONLY a lesson topic.

You must generate a structured lesson.

Return ONLY valid JSON (no explanation, no markdown, no extra text).

JSON structure:

{
    "topic": "string",
    "lesson": "German explanation of the topic with arabic transalation",
   "sentences": [
  "10 simple German sentences with Arabic translation for each sentence"
],
    "exercises": ["15 beginner-friendly exercises in German"],
    "solutions_arabic": ["Arabic explanations/solutions for each exercise"],
    "questions": ["5 questions"],
    "answers": ["5 answers"]
}

Rules:
- Lesson MUST be in German
- Sentences MUST be in German
- Exercises MUST be in German
- Solutions MUST be in Arabic
- Questions MUST be simple and clear
- Answers MUST match questions
- Return ONLY valid JSON
- No extra text outside JSON
PROMPT;

        $response = Http::timeout(90)
            ->connectTimeout(15)
            ->retry(2, 1500)
            ->withHeaders([
            'Authorization' => 'Bearer ' . env('NVIDIA_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://integrate.api.nvidia.com/v1/chat/completions', [
            'model' => 'meta/llama-3.3-70b-instruct',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $prompt
                ],
                [
                    'role' => 'user',
                    'content' => $topic
                ]
            ],
            'temperature' => 0.2,
            'top_p' => 0.7,
            'max_tokens' => 768,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('AI provider returned an unexpected response.');
        }

        $data = $response->json();
        $content = data_get($data, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI provider returned an empty lesson payload.');
        }

        $decoded = $this->tryParseJsonString($content);
        if (! is_array($decoded)) {
            $decoded = $this->extractFirstJsonObject($content) ?: [
                'topic' => $topic,
                'lesson' => $content,
                'sentences' => [],
                'exercises' => [],
                'solutions_arabic' => [],
                'questions' => [],
                'answers' => [],
            ];
        }

        return $this->normalizeLessonPayload($decoded ?: [
            'topic' => $topic,
            'lesson' => $content,
            'sentences' => [],
            'exercises' => [],
            'solutions_arabic' => [],
            'questions' => [],
            'answers' => [],
        ]);
    }
}