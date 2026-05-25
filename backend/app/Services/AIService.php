<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class AIService
{
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
    "lesson": "German explanation of the topic",
    "sentences": ["5 simple German sentences"],
    "exercises": ["15 diffrent exercises in German"],
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

        $content = $this->getMessageContent($data);

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI provider returned an empty lesson payload.');
        }

        // Try to extract a JSON object from the content (some models wrap JSON in text)
        $jsonString = $this->findFirstJsonObject($content);

        if ($jsonString !== null) {
            $decoded = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Try decoding the whole content as JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback: return a minimal structure with the raw content in `lesson`
        return [
            'topic' => $topic,
            'lesson' => $content,
            'sentences' => [],
            'exercises' => [],
            'solutions_arabic' => [],
            'questions' => [],
            'answers' => [],
        ];
    }

    /**
     * Attempt to locate the most likely text content in provider responses.
     */
    protected function getMessageContent(array $data): ?string
    {
        $candidates = [
            data_get($data, 'choices.0.message.content'),
            data_get($data, 'choices.0.delta.content'),
            data_get($data, 'choices.0.content.0.text'),
            data_get($data, 'outputs.0.content.0.text'),
            data_get($data, 'result.choices.0.text'),
            data_get($data, 'data.0.text'),
            data_get($data, 'content'),
        ];

        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return $c;
            }
            if (is_array($c)) {
                $joined = implode('', array_map(function ($part) {
                    if (is_string($part)) return $part;
                    if (is_array($part) && isset($part['text'])) return $part['text'];
                    return '';
                }, $c));

                if (trim($joined) !== '') {
                    return $joined;
                }
            }
        }

        // As a last resort, walk the structure for the first non-empty string
        $found = null;
        array_walk_recursive($data, function ($v) use (&$found) {
            if ($found === null && is_string($v) && trim($v) !== '') {
                $found = $v;
            }
        });

        return is_string($found) ? $found : null;
    }

    /**
     * Find the first balanced JSON object in a string, or null if none found.
     */
    protected function findFirstJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $len = strlen($text);
        $depth = 0;
        for ($i = $start; $i < $len; $i++) {
            $char = $text[$i];
            if ($char === '{') $depth++;
            if ($char === '}') $depth--;

            if ($depth === 0) {
                $candidate = substr($text, $start, $i - $start + 1);
                json_decode($candidate);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $candidate;
                }

                // try to find next opening brace
                $start = strpos($text, '{', $start + 1);
                if ($start === false) break;
                $i = $start - 1;
                $depth = 0;
            }
        }

        return null;
    }
}