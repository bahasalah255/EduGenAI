<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class AIService
{
    private function buildFallbackLesson(string $topic): array
    {
        $topicLabel = trim($topic) !== '' ? trim($topic) : 'Deutsch Grammatik';

        $lessonText = implode("\n\n", [
            'Thema: ' . $topicLabel,
            'شرح عربي: هذا الدرس يقدم ملخصًا عمليًا ومبسّطًا حول الموضوع مع أمثلة ألمانية حديثة وترجمة عربية واضحة.',
            'Wichtige Regel: Achte auf die Form, die Funktion und den Kontext im Satz.',
            'Arabisch: ركّز على شكل الكلمة داخل الجملة، لأن القاعدة تتغيّر حسب السياق.',
            'Exam Tip: Prüfe in Goethe, telc und ÖSD immer die Satzumgebung, bevor du antwortest.',
        ]);

        $sentences = [
            'Ich brauche heute das richtige Wort für ' . $topicLabel . '.',
            'Die Lehrerin erklärt die Regel sehr klar und einfach.',
            'Im Kurs üben wir neue Beispiele mit Alltagssprache.',
            'Der Text ist kurz, aber sehr hilfreich für die Prüfung.',
            'Meine Freunde wiederholen die Grammatik jeden Abend.',
            'Wir schreiben die Lösung zuerst auf ein Blatt Papier.',
            'Das neue Kapitel passt gut zum Thema der Lektion.',
            'Viele Lernende machen denselben Fehler am Anfang.',
            'Der Trainer gibt einen wichtigen Hinweis für die Prüfung.',
            'Am Ende vergleichen wir die Antwort mit der Regel.',
        ];

        $questions = [
            'Was ist das Hauptthema der Lektion?',
            'Warum ist die Regel für Lernende wichtig?',
            'Worauf soll man im Satz besonders achten?',
            'Welche Prüfungstipps sind hier relevant?',
            'Wie soll man die Regel am besten üben?',
        ];

        $answers = [
            $topicLabel,
            'Weil sie im Alltag und in Prüfungen häufig gebraucht wird.',
            'Auf die Satzumgebung, die Form und die Funktion des Wortes.',
            'Goethe, telc und ÖSD, besonders bei Grammatik und Textaufgaben.',
            'Mit vielen kurzen Beispielen, Wiederholung und kontrollierten Übungen.',
        ];

        $exerciseGroups = [
            [
                'group_title' => 'A2 Lückentext',
                'exercises' => [
                    'Ergänze die richtige Form in diesem Satz: Ich lese ___ Text.',
                    'Setze das passende Wort ein: Das ist ___ gute Beispiel.',
                    'Wähle die korrekte Lösung: Wir brauchen ___ Antwort.',
                    'Ergänze: Die Lehrerin erklärt ___ Regel noch einmal.',
                    'Fülle aus: Heute lernen wir ___ wichtige Thema.',
                ],
                'solutions_arabic' => [
                    'الإجابة: den. السبب: الكلمة المطلوبة تتبع القاعدة المناسبة داخل الجملة حسب الوظيفة النحوية.',
                    'الإجابة: ein. السبب: المثال يحتاج أداة نكرة مع صياغة صحيحة للصفة.',
                    'الإجابة: eine. السبب: التوافق بين الأداة والاسم هو أساس الحل الصحيح هنا.',
                    'الإجابة: die. السبب: المطلوب أداة تعريف مناسبة مع مطابقة القاعدة في الجملة.',
                    'الإجابة: ein. السبب: هذا التركيب شائع جدًا في تمارين A2 ويحتاج انتباهًا للأداة.',
                ],
            ],
            [
                'group_title' => 'B1 Satzbildung',
                'exercises' => [
                    'Forme den Satz um: Der Schüler macht die Aufgabe langsam.',
                    'Verbinde die Teile zu einem korrekten Satz.',
                    'Schreibe einen Satz mit dem Thema ' . $topicLabel . '.',
                    'Erkläre die Regel mit einem eigenen Beispiel.',
                    'Ergänze den Satz so, dass er grammatisch korrekt ist.',
                ],
                'solutions_arabic' => [
                    'الحل: الجملة الصحيحة يجب أن تحافظ على ترتيب الكلمات وتوافق القاعدة المطلوبة.',
                    'الحل: نربط الأجزاء بحسب المعنى والتركيب النحوي الصحيح.',
                    'الحل: يمكن صياغة جملة جديدة بالاعتماد على نفس القاعدة مع مثال واقعي.',
                    'الحل: التفسير بالعربية يثبت فهم القاعدة وليس الحفظ فقط.',
                    'الحل: راقب الفعل، الأداة، والصفة قبل تثبيت الإجابة النهائية.',
                ],
            ],
            [
                'group_title' => 'B1 Kommunikation',
                'exercises' => [
                    'Antworte auf die Frage in einem vollständigen Satz.',
                    'Schreibe eine kurze Erklärung für deinen Mitschüler.',
                    'Übersetze den Satz ins Arabische und erkläre die Regel.',
                    'Korrigiere den Fehler im Satz und begründe deine Antwort.',
                    'Wähle die beste Lösung und erkläre sie mündlich.',
                ],
                'solutions_arabic' => [
                    'الإجابة: يجب أن تكون الجملة كاملة وواضحة وتناسب سياق السؤال.',
                    'الإجابة: الشرح القصير يساعد على تثبيت القاعدة بطريقة عملية.',
                    'الإجابة: الترجمة العربية تكشف المعنى، ثم نربطه بالقاعدة الألمانية.',
                    'الإجابة: نحدد الخطأ أولًا ثم نفسر لماذا كان الحل الآخر خاطئًا.',
                    'الإجابة: الاختيار الصحيح هو الذي ينسجم مع القاعدة ومعنى الجملة.',
                ],
            ],
        ];

        $flatExercises = array_merge(...array_map(fn ($group) => $group['exercises'], $exerciseGroups));
        $flatSolutions = array_merge(...array_map(fn ($group) => $group['solutions_arabic'], $exerciseGroups));

        return $this->normalizeLessonPayload([
            'topic' => $topicLabel,
            'lesson' => $lessonText,
            'sentences' => $sentences,
            'exercises' => $flatExercises,
            'solutions_arabic' => $flatSolutions,
            'questions' => $questions,
            'answers' => $answers,
            'exercise_groups' => $exerciseGroups,
        ]);
    }

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
أنت مؤلف وخبير أكاديمي متخصص في تأليف كتب تعليم اللغة الألمانية للناطقين باللغة العربية، ولديك خبرة طويلة في إعداد المناهج التعليمية الاحترافية المشابهة لكتب Hueber وKlett وCornelsen.

مهمتك هي إنشاء كتاب PDF احترافي كامل حول الدرس التالي:

THEMA: Adjektivendungen
نهايات الصفات حسب الأداة

المستوى

A2 – B1

الفئة المستهدفة

الطلاب العرب الذين يتعلمون اللغة الألمانية ويريدون الانتقال من المستوى A2 إلى B1 والاستعداد لاختبارات Goethe وtelc وÖSD.

معايير الجودة

يجب أن يبدو الكتاب وكأنه صادر عن دار نشر متخصصة في تعليم اللغة الألمانية.

يجب أن يكون المحتوى:

أكاديمياً.
احترافياً.
خالياً من الأخطاء.
مناسباً للطباعة.
جاهزاً للبيع التجاري.
غنيّاً بالأمثلة الواقعية.
بعيداً عن الحشو والتكرار.
باللغة الألمانية مع شرح كامل باللغة العربية.

التصميم

يجب أن يحتوي كل صفحة على:

عنوان الدرس.
شعار VIZARTTES.
Deutsch als Fremdsprache.
رقم الصفحة.
تصميم واضح بعناوين رئيسية وفرعية.
مربعات ملاحظات.
مربعات Tricks.
مربعات Exam Tips.
تنسيق مناسب للتحويل إلى PDF.

الصفحة 1

غلاف احترافي

اسم الدرس

VIZARTTES

Deutsch als Fremdsprache

تصميم جذاب يشبه أغلفة الكتب الاحترافية.

الصفحة 2

Cheat Sheet

يتضمن:

أهم قواعد الدرس
15 قاعدة ذهبية
أهم الاستثناءات
أكثر الأخطاء انتشاراً
خطوات تذكر القاعدة بسرعة
Exam Tips
Tricks للحفظ

الصفحة 3

شرح القاعدة بالتفصيل

يشمل:

متى تستخدم؟
متى لا تستخدم؟
الفرق بينها وبين القواعد المشابهة
حالات خاصة
ملاحظات الامتحانات
شرح عربي كامل

الصفحة 4

جدول شامل

القاعدة
الاستخدام
التركيب
مثال
الترجمة
ملاحظات

الصفحات 5–6

Vocabulary Glossary

100 كلمة مهمة

لكل كلمة:

Artikel
Plural
Bedeutung
Arabisch
مثال ألماني
ترجمة المثال

الصفحات 7–8

أكثر 50 فعلاً استعمالاً

لكل فعل:

Infinitiv
Präsens
Präteritum
Perfekt
Bedeutung
مثال
ترجمة

الصفحات 9–10

50 جملة تطبيقية

Deutsch

Arabisch

مع توضيح سبب استعمال القاعدة.

الصفحات 11–12

40 سؤال وجواب

Frage

Antwort

شرح عربي بعد كل جواب.

الصفحات 13–14

40 خطأ شائع

يتضمن:

الجملة الخاطئة
الجملة الصحيحة
لماذا؟

الشرح بالعربية.

الصفحات 15–16

جدول مقارنة شامل

بين جميع القواعد المشابهة.

مثال:

als / wenn
seit / vor
obwohl / trotzdem
denn / weil
müssen / sollen
وغيرها حسب الدرس.
الصفحة 17

Mind Map

ملخص شامل للحفظ السريع.

الصفحات 18–23

تمارين المستوى A2

ما لا يقل عن 180 سؤالاً.

تنويع التمارين:

Lückentext
Satzbildung
Verbkonjugation
Wortstellung
Artikel
Präpositionen
Dialoge
Zuordnen
Ergänzen
Übersetzen

الصفحات 24–30

تمارين المستوى B1

ما لا يقل عن 220 سؤالاً.

تنويع التمارين:

Grammatik
Schreiben
Satzumformung
Verbformen
Nebensätze
Konnektoren
Textverständnis
Fehlerkorrektur
Wortschatz
Kommunikation

الصفحات 31–34

Multiple Choice

250 سؤالاً

أربع اختيارات لكل سؤال.

الصفحات 35–37

Fehler finden

200 سؤال

يجد الطالب الخطأ ويصححه.

الصفحة 38

نص طويل

800–1000 كلمة

يستخدم جميع قواعد الدرس.

ثم:

ترجمة عربية كاملة.

ثم:

شرح جميع التراكيب المهمة.

ثم:

استخراج المفردات الجديدة.

الصفحة 39

الاختبار النهائي

100 سؤال

يشمل:

A2

B1

Grammar

Vocabulary

Reading

Writing

مع سلم التنقيط.

الصفحات 40–55

الحلول النموذجية

يجب حل جميع التمارين الموجودة في الكتاب.

لكل سؤال:

الحل الصحيح.
لماذا هذا هو الجواب الصحيح؟
شرح القاعدة بالعربية.
سبب خطأ الاختيارات الأخرى (إذا كان سؤال اختيار متعدد).
نصيحة لتجنب الخطأ مستقبلاً.
Trick للحفظ.

الصفحات 56–60

ملحق احترافي

يشمل:

أهم 300 مفردة خاصة بالدرس.
أهم 100 تعبير يستخدمه الألمان.
أهم الأخطاء التي يقع فيها العرب.
نصائح لاجتياز امتحان Goethe A2.
نصائح لاجتياز امتحان Goethe B1.
جدول مراجعة في 30 دقيقة.
خطة مراجعة لمدة 7 أيام.
خطة مراجعة لمدة 30 يوماً.
قائمة "احفظ قبل الامتحان".
Checklist نهائية للتأكد من إتقان الدرس.

تعليمات إلزامية
لا تختصر المحتوى.
لا تكرر الأمثلة.
استخدم مفردات حديثة وشائعة في الحياة اليومية.
اجعل جميع التمارين جديدة وغير مكررة.
بعد كل مجموعة تمارين، لا تعرض الحلول مباشرة؛ اجمع جميع الحلول في قسم الحلول النموذجية.
يجب أن تكون شروحات الحلول باللغة العربية الفصحى، واضحة ومبسطة، مع ذكر سبب صحة الإجابة وربطها بالقاعدة.
أضف ملاحظات خاصة بامتحانات Goethe وtelc وÖSD كلما كان ذلك مناسباً.
اجعل الكتاب يبدو كأنه مرجع احترافي يمكن بيعه في المتاجر الإلكترونية أو طباعته ونشره دون الحاجة إلى تعديلات إضافية.

المطلوب هنا أن يكون الناتج منظماً كبيانات JSON فقط، وأن يحتوي على تمارين مختلفة بالفعل مع حلولها العربية المنفصلة، وأن تكون التمارين غير مكررة داخل المجموعة الواحدة وبين المجموعات المختلفة.

Return ONLY valid JSON. Do not add markdown, commentary, or any text outside JSON.

JSON structure:

{
    "topic": "string",
    "lesson": "A long structured lesson in German with Arabic explanations, formatted as plain text with clear sections and page-like headings.",
    "sentences": [
        "Short German examples with Arabic meaning, chosen to support the lesson.",
        "Another distinct example sentence."
    ],
    "questions": [
        "Simple question 1",
        "Simple question 2"
    ],
    "answers": [
        "Matching answer 1",
        "Matching answer 2"
    ],
    "exercise_groups": [
        {
            "group_title": "A2 Lückentext",
            "exercises": [
                "German exercise 1",
                "German exercise 2"
            ],
            "solutions_arabic": [
                "Arabic solution and explanation 1",
                "Arabic solution and explanation 2"
            ]
        }
    ]
}

Rules:
- Output JSON only.
- Keep exercises and solutions separate.
- Do not repeat the same exercise wording.
- Make every exercise different from the others.
- Make every solution explain the answer in Arabic.
- If you include multiple exercise groups, each group must target a different exercise type.
- Do not place solutions inside the exercise text.
- Answers must correspond exactly to questions.
- Use natural, realistic German.
- Use clear Arabic explanations.
PROMPT;

        $apiKey = env('NVIDIA_API_KEY');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return $this->buildFallbackLesson($topic);
        }

        try {
            $response = Http::timeout(90)
                ->connectTimeout(15)
                ->retry(2, 1500)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://integrate.api.nvidia.com/v1/chat/completions', [
                    'model' => 'meta/llama-3.3-70b-instruct',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $prompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $topic,
                        ],
                    ],
                    'temperature' => 0.2,
                    'top_p' => 0.7,
                    'max_tokens' => 4096,
                ]);

            if (! $response->successful()) {
                return $this->buildFallbackLesson($topic);
            }

            $data = $response->json();
            $content = data_get($data, 'choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return $this->buildFallbackLesson($topic);
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
        } catch (Throwable $throwable) {
            return $this->buildFallbackLesson($topic);
        }
    }
}