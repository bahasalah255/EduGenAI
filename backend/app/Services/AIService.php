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
    "sentences": ["5 simple sentences in German"],
    "exercises": ["15 different beginner-friendly exercises in German"],
    "solutions_arabic": ["15 Arabic corrections, one correction for each exercise"],
    "questions": ["5 questions"],
    "answers": ["5 answers"]
}

Rules:
- Lesson MUST be in German
- Sentences MUST be in German
- Generate EXACTLY 5 sentences in `sentences`
- Exercises MUST be in German
- Solutions MUST be in Arabic
- Generate EXACTLY 15 exercises in `exercises`
- All 15 exercises MUST be different from each other
- Generate EXACTLY 15 Arabic corrections in `solutions_arabic`
- Each correction in `solutions_arabic` MUST match its exercise by order (exercise 1 -> correction 1, etc.)
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
            'max_tokens' => 1400,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('AI provider returned an unexpected response.');
        }

        $data = $response->json();
        $content = data_get($data, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI provider returned an empty lesson payload.');
        }

        return json_decode($content, true) ?: [
            'topic' => $topic,
            'lesson' => $content,
            'sentences' => [],
            'exercises' => [],
            'solutions_arabic' => [],
            'questions' => [],
            'answers' => [],
        ];
    }
}