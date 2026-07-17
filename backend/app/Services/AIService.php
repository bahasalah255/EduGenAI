<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIService
{
    private function normalizeStringArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (is_array($item) || is_object($item)) {
                return '';
            }

            return trim((string) $item);
        }, $value)));
    }

    private function normalizeVocabularyEntries($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $entries = [];

        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $entries[] = [
                'german' => trim((string) ($item['german'] ?? $item['word'] ?? $item['term'] ?? '')),
                'arabic' => trim((string) ($item['arabic'] ?? $item['meaning_arabic'] ?? $item['translation'] ?? '')),
                'example' => trim((string) ($item['example'] ?? $item['example_german'] ?? '')),
                'pronunciation' => trim((string) ($item['pronunciation'] ?? $item['pronunciation_notes'] ?? '')),
            ];
        }

        return array_values(array_filter($entries, function (array $entry) {
            return $entry['german'] !== '' || $entry['arabic'] !== '' || $entry['example'] !== '';
        }));
    }

    private function normalizeExerciseGroups($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $groups = [];

        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $questions = $this->normalizeStringArray($item['questions'] ?? $item['exercises'] ?? []);
            $answers = $this->normalizeStringArray($item['answers'] ?? []);
            $solutions = $this->normalizeStringArray($item['solutions_arabic'] ?? $item['solutionsArabic'] ?? $item['solutions'] ?? []);

            $groups[] = [
                'title' => trim((string) ($item['title'] ?? $item['group_title'] ?? $item['name'] ?? '')),
                'type' => trim((string) ($item['type'] ?? 'exercise')),
                'instructions' => trim((string) ($item['instructions'] ?? '')),
                'questions' => $questions,
                'answers' => $answers,
                'solutions_arabic' => $solutions,
                'exercises' => $questions,
            ];
        }

        return array_values(array_filter($groups, function (array $group) {
            return $group['title'] !== '' || !empty($group['questions']) || !empty($group['solutions_arabic']);
        }));
    }

    private function validatePayload(array $data): bool
    {
        // 1. Must have at least a title or topic
        if (empty($data['title']) && empty($data['topic'])) {
            Log::warning("Validation failed: no title or topic found");
            return false;
        }

        // 2. Must have at least some vocabulary (>= 3)
        if (empty($data['vocabulary']) || count($data['vocabulary']) < 3) {
            Log::warning("Validation failed: vocabulary count is " . (isset($data['vocabulary']) ? count($data['vocabulary']) : 0));
            return false;
        }

        // 3. Must have at least some exercise groups (>= 3)
        if (empty($data['exercise_groups']) || count($data['exercise_groups']) < 3) {
            Log::warning("Validation failed: exercise_groups count is " . (isset($data['exercise_groups']) ? count($data['exercise_groups']) : 0));
            return false;
        }

        // 4. Each exercise group must have at least 1 question with 1 answer
        foreach ($data['exercise_groups'] as $gi => $group) {
            $questions = $group['questions'] ?? $group['exercises'] ?? [];
            if (count($questions) < 1) {
                Log::warning("Validation failed: group " . $gi . " has no questions");
                return false;
            }
        }

        return true;
    }

    private function normalizeLessonPayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? $data['topic'] ?? 'Deutsch Lektion'));
        $level = trim((string) ($data['level'] ?? 'A2-B1'));
        $topic = trim((string) ($data['topic'] ?? ''));
        $lesson = trim((string) ($data['lesson'] ?? ''));

        $grammarPoints = $this->normalizeStringArray($data['grammar_points'] ?? []);
        $vocabulary = $this->normalizeVocabularyEntries($data['vocabulary'] ?? []);
        $examples = $this->normalizeStringArray($data['examples'] ?? $data['sentences'] ?? []);
        $sentences = $this->normalizeStringArray($data['sentences'] ?? $data['examples'] ?? []);
        $questions = $this->normalizeStringArray($data['questions'] ?? []);
        $answers = $this->normalizeStringArray($data['answers'] ?? []);
        $exerciseGroups = $this->normalizeExerciseGroups($data['exercise_groups'] ?? []);

        $learningObjectives = $this->normalizeStringArray($data['learning_objectives'] ?? []);
        
        $commonMistakes = [];
        if (isset($data['common_mistakes']) && is_array($data['common_mistakes'])) {
            foreach ($data['common_mistakes'] as $cm) {
                if (is_array($cm)) {
                    $commonMistakes[] = [
                        'mistake' => trim((string) ($cm['mistake'] ?? '')),
                        'correction' => trim((string) ($cm['correction'] ?? '')),
                        'explanation_arabic' => trim((string) ($cm['explanation_arabic'] ?? $cm['explanation'] ?? '')),
                    ];
                }
            }
        }

        $usageNotes = $this->normalizeStringArray($data['usage_notes'] ?? []);
        $grammarTips = $this->normalizeStringArray($data['grammar_tips'] ?? []);
        $memoryTricks = $this->normalizeStringArray($data['memory_tricks'] ?? []);
        $summary = trim((string) ($data['summary'] ?? $data['final_summary'] ?? ''));

        return [
            'title' => $title,
            'level' => $level,
            'topic' => $topic,
            'lesson' => $lesson,
            'grammar_points' => $grammarPoints,
            'vocabulary' => $vocabulary,
            'examples' => $examples,
            'sentences' => $sentences,
            'questions' => $questions,
            'answers' => $answers,
            'exercise_groups' => $exerciseGroups,
            'learning_objectives' => $learningObjectives,
            'common_mistakes' => $commonMistakes,
            'usage_notes' => $usageNotes,
            'grammar_tips' => $grammarTips,
            'memory_tricks' => $memoryTricks,
            'summary' => $summary,
        ];
    }

    private function mergeLessonPayload(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    $base[$key] = $value;
                }
                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                $base[$key] = trim($value);
                continue;
            }

            if (!is_array($value) && $value !== null && $value !== '') {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function buildFallbackLesson(string $topic): array
    {
        $topicLabel = trim($topic) !== '' ? trim($topic) : 'Deutsch Grammatik';

        $lessonText = implode("\n\n", [
            'Thema: ' . $topicLabel,
            'Schritt-für-Schritt Grammatikerklärung:',
            '1. Einleitung:',
            'Dieses akademische Arbeitsbuch wurde speziell für arabischsprachige Deutschlerner konzipiert. Es soll Ihnen helfen, die Struktur von "' . $topicLabel . '" vollkommen zu durchdringen. Wir konzentrieren uns hierbei auf die exakte grammatikalische Funktion im Satzkontext.',
            '2. Hauptregeln & Formen:',
            'Um "' . $topicLabel . '" richtig anzuwenden, müssen Sie die drei Säulen der deutschen Grammatik prüfen: Artikel (Bestimmt/Unbestimmt), Numerus (Singular/Plural) und den Kasus (Nominativ, Akkusativ, Dativ, Genitiv). Die korrekte Deklination erfolgt schrittweise, indem man die Endungen an das jeweilige Nomen anpasst.',
            '3. Typische Fehler & Hürden:',
            'Oft verwechseln Lerner den Dativ und Akkusativ oder vergessen die Endungen nach unbestimmten Artikeln. Achten Sie darauf, ob ein Verb oder eine Präposition den Fall bestimmt. So verlangt das Verb "helfen" immer den Dativ, während "sehen" den Akkusativ verlangt.'
        ]);

        $learningObjectives = [
            'Die Systematik von "' . $topicLabel . '" im Satzkontext verstehen.',
            'Die korrekten Endungen in den Fällen Dativ und Akkusativ anwenden.',
            'Typische Grammatikfehler im Alltag sicher erkennen und vermeiden.'
        ];

        $grammarPoints = [
            'Achte auf die richtige Funktion der Endung im Satzkontext.',
            'Unterscheide zwischen starker, schwacher und gemischter Deklination.',
            'Prüfe Artikel, Kasus und Genus vor der Zuweisung der Endung.',
            'Nutze klare Regelprüfung statt Auswendiglernen einzelner Wortformen.',
            'Vergleiche die Form stets mit dem Beispielsatz im realen Kontext.',
        ];

        $grammarTips = [
            'Tipp: Verben wie helfen, danken, gefallen und gehören brauchen immer den Dativ.',
            'Tipp: Präpositionen wie mit, nach, von, zu und seit regieren immer den Dativ.'
        ];

        $memoryTricks = [
            'Eselsbrücke: "Mit, nach, von, seit, zu, aus, bei - verlangen Dativ, das ist keine Hexerei!"',
            'Eselsbrücke: Akkusativ fragt nach "Wen/Was" (Aktion/Bewegung), Dativ fragt nach "Wem" (Zustand/Ort).'
        ];

        $commonMistakes = [
            [
                'mistake' => 'Ich helfe den Mann.',
                'correction' => 'Ich helfe dem Mann.',
                'explanation_arabic' => 'الخطأ هنا هو استخدام أداة النصب "den". الفعل "helfen" يتطلب دائماً مجروراً (Dativ)، والاسم "Mann" مذكر، لذا تصبح الأداة "dem".'
            ],
            [
                'mistake' => 'Wir fahren mit das Auto.',
                'correction' => 'Wir fahren mit dem Auto.',
                'explanation_arabic' => 'حرف الجر "mit" يتطلب حالة الجر (Dativ) دائماً. بما أن "Auto" محايد (das Auto), فإن أداة التعريف تصبح "dem".'
            ],
            [
                'mistake' => 'Das ist ein gutes Lehrer.',
                'correction' => 'Das ist ein guter Lehrer.',
                'explanation_arabic' => 'الاسم "Lehrer" مذكر في حالة الرفع (Nominativ) بعد "sein". عند استخدام أداة النكرة "ein", تنتهي الصفة بـ "er".'
            ]
        ];

        $usageNotes = [
            'Im gesprochenen Deutsch wird der Dativ oft anstelle des Genitivs verwendet (z.B. "wegen dem Regen" statt "wegen des Regens").',
            'Achten Sie bei Wechselpräpositionen (in, auf, an...) auf den Unterschied: Dativ bei Standorten, Akkusativ bei Richtungen.'
        ];

        $vocabulary = [
            ['german' => 'das Nomen, -', 'arabic' => 'الاسم', 'example' => 'Ein Nomen beschreibt Personen, Sachen oder Konzepte.', 'pronunciation' => 'نطق: داس نُومِن'],
            ['german' => 'das Adjektiv, -e', 'arabic' => 'الصفة', 'example' => 'Das Adjektiv beschreibt ein Nomen näher.', 'pronunciation' => 'نطق: داس أدْيِكْتِيف'],
            ['german' => 'der Artikel, -', 'arabic' => 'أداة التعريف', 'example' => 'Der Artikel zeigt das Geschlecht des Nomens.', 'pronunciation' => 'نطق: دِر أرْتِيكِل'],
            ['german' => 'der Kasus, -', 'arabic' => 'الحالة الإعرابية', 'example' => 'Es gibt vier Fälle im Deutschen.', 'pronunciation' => 'نطق: دِر كَازُوس'],
            ['german' => 'der Dativ, -e', 'arabic' => 'حالة الجر', 'example' => 'Der Dativ wird nach bestimmten Verben verwendet.', 'pronunciation' => 'نطق: دِر دَاتِيف'],
            ['german' => 'der Akkusativ, -e', 'arabic' => 'حالة Nصب', 'example' => 'Der Akkusativ zeigt das direkte Objekt.', 'pronunciation' => 'نطق: دِر أكُوزَاتِيف'],
            ['german' => 'das Verb, -en', 'arabic' => 'الفعل', 'example' => 'Das Verb beschreibt die Handlung im Satz.', 'pronunciation' => 'نطق: داس فِيرْب'],
            ['german' => 'die Präposition, -en', 'arabic' => 'حرف الجر', 'example' => 'Einige Präpositionen verlangen immer den Dativ.', 'pronunciation' => 'نطق: دِي بِرِيبُوزِيتْسيُون'],
            ['german' => 'die Deklination, -en', 'arabic' => 'التصريف الصرفي', 'example' => 'Die Deklination passt das Adjektiv an das Nomen.', 'pronunciation' => 'نطق: دِي دِيكْلِينَاتْسْيُون'],
            ['german' => 'die Endung, -en', 'arabic' => 'النهاية الصرفية', 'example' => 'Die Endung verändert sich je nach Kasus.', 'pronunciation' => 'نطق: دِي إِنْدُونْغ'],
            ['german' => 'die Regel, -n', 'arabic' => 'القاعدة', 'example' => 'Wir müssen die grammatikalische Regel verstehen.', 'pronunciation' => 'نطق: دِي رِيجِل'],
            ['german' => 'das Beispiel, -e', 'arabic' => 'المثال', 'example' => 'Ein Beispiel verdeutlicht die Erklärung.', 'pronunciation' => 'نطق: داس بَايْشْبِيل'],
            ['german' => 'die Übung, -en', 'arabic' => 'التمرين', 'example' => 'Die Übung hilft beim Lernen.', 'pronunciation' => 'نطق: دِي أُوبُونْغ'],
            ['german' => 'die Lösung, -en', 'arabic' => 'الحل', 'example' => 'Die Lösung steht am Ende des Heftes.', 'pronunciation' => 'نطق: دِي لُوزُونْغ'],
            ['german' => 'die Prüfung, -en', 'arabic' => 'الامتحان', 'example' => 'Wir bereiten uns auf die Goethe-Prüfung vor.', 'pronunciation' => 'نطق: دِي بْرُوفُونْغ'],
        ];

        $examples = [
            'Ich helfe dem alten Mann. - أساعد الرجل العجوز. (تم استخدام حالة الجر Dativ لأن الفعل helfen يتطلب مفعولاً به في حالة الجر، والاسم Mann مذكر وأداته dem، لذا تأخذ الصفة النهاية en.)',
            'Wir gehen mit einem neuen Freund. - نذهب مع صديق جديد. (بعد حرف الجر mit نستخدم Dativ. الاسم Freund مذكر وأداة النكرة einem، لذا تأخذ الصفة النهاية en.)',
            'Sie wohnt in einer großen Stadt. - هي تسكن في مدينة كبيرة. (حرف الجر in هنا يدل على السكون فيحتاج Dativ. الاسم Stadt مؤنث وأداته einer، لذا تأخذ الصفة النهاية en.)',
            'Er schenkt dem Kind ein schönes Buch. - هو يهدي الطفل كتاباً جميلاً. (الطفل Kind محايد في حالة الجر dem. الكتاب Buch محايد في حالة النصب Akkusativ مسبوق بأداة نكرة ein، لذا تأخذ الصفة النهاية es.)',
            'Nach dem Essen trinken wir Kaffee. - بعد الطعام نشرب القهوة. (حرف الجر nach يتطلب حالة الجر Dativ دائماً، والاسم Essen محايد وأداته dem.)',
            'Aus diesem Grund lernen wir Deutsch. - لهذا السبب نتعلم الألمانية. (حرف الجر aus يتطلب Dativ دائماً، واسم الإشارة diesem يتوافق مع Grund المذكر.)',
            'Seit einem Jahr wohne ich in Berlin. - منذ عام أعيش في برلين. (حرف الجر seit يتطلب Dativ دائماً، الاسم Jahr محايد وأداته einem.)',
            'Gegenüber dem Bahnhof liegt die Schule. - مقابل محطة القطار تقع المدرسة. (حرف الجر gegenüber يتطلب Dativ دائماً، الاسم Bahnhof مذكر وأداته dem.)',
            'Ich kaufe einen schnellen Computer. - أشتري حاسوباً سريعاً. (الاسم Computer مذكر في حالة النصب Akkusativ مسبوق بأداة نكرة einen، لذا تأخذ الصفة النهاية en.)',
            'Das ist eine wichtige Lektion für uns. - هذا درس مهم بالنسبة لنا. (الاسم Lektion مؤنث في حالة الرفع Nominativ مسبوق بأداة نكرة eine، لذا تأخذ الصفة النهاية e.)',
        ];

        $exerciseGroups = [
            [
                'title' => 'Übung 1: Lückentext (Fill in the blanks) - Niveau: Leicht',
                'type' => 'fill_blank',
                'instructions' => 'Ergänzen Sie die passende Endung (em, er, en).',
                'questions' => [
                    'Ich helfe mein___ Vater (männlich).',
                    'Er schreibt mit ein___ Stift (männlich).',
                    'Wir wohnen in ein___ Haus (neutral).'
                ],
                'answers' => ['em', 'em', 'em'],
                'solutions_arabic' => [
                    'الجواب هو "em" لأن الاسم "Vater" مذكر مجرور (Dativ) بعد الفعل "helfen".',
                    'الجواب هو "em" لأن الاسم "Stift" مذكر مجرور (Dativ) بعد حرف الجر "mit".',
                    'الجواب هو "em" لأن الاسم "Haus" محايد مجرور (Dativ) بعد حرف الجر "in".'
                ]
            ],
            [
                'title' => 'Übung 2: Mehrfachauswahl (Choose the correct answer) - Niveau: Leicht',
                'type' => 'multiple_choice',
                'instructions' => 'Wählen Sie die richtige Option.',
                'questions' => [
                    'Ich sehe den [Hund / Hundes / Hundem] im Park.',
                    'Wir gehen [zu / nach / mit] dem Bahnhof.',
                    'Das ist ein [guter / gutem / guten] Lehrer.'
                ],
                'answers' => ['Hund', 'zu', 'guter'],
                'solutions_arabic' => [
                    'الجواب هو "Hund" لأنه مفعول به مباشر منصوب (Akkusativ) بعد الفعل "sehen".',
                    'الجواب هو "zu" لأنه حرف جر يتطلب Dativ للتعبير عن الوجهة.',
                    'الجواب هو "guter" لأن الصفة تأخذ النهاية "er" في حالة الرفع المذكر بعد "ein".'
                ]
            ],
            [
                'title' => 'Übung 3: Zuordnung (Match items together) - Niveau: Leicht',
                'type' => 'matching',
                'instructions' => 'Ordnen Sie den richtigen Fall zu.',
                'questions' => [
                    'Die Präposition "mit" verlangt...',
                    'Die Präposition "für" verlangt...',
                    'Die Präposition "wegen" verlangt...'
                ],
                'answers' => ['den Dativ', 'den Akkusativ', 'den Genitiv'],
                'solutions_arabic' => [
                    'الجواب Dativ لأن "mit" يجر دائماً.',
                    'الجواب Akkusativ لأن "für" ينصب دائماً.',
                    'الجواب Genitiv لأن "wegen" يضاف إليه.'
                ]
            ],
            [
                'title' => 'Übung 4: Richtig oder Falsch (True or False) - Niveau: Leicht',
                'type' => 'true_false',
                'instructions' => 'Richtig oder Falsch?',
                'questions' => [
                    'Das Verb "helfen" braucht den Akkusativ.',
                    'Die Präposition "mit" wird immer mit dem Dativ verwendet.',
                    'Die Präposition "für" braucht immer den Akkusativ.'
                ],
                'answers' => ['Falsch', 'Richtig', 'Richtig'],
                'solutions_arabic' => [
                    'الجواب "Falsch" لأن "helfen" يأخذ Dativ وليس Akkusativ.',
                    'الجواب "Richtig" لأن "mit" هي أداة جر Dativ ثابتة.',
                    'الجواب "Richtig" لأن "für" هي أداة نصب Akkusativ ثابتة.'
                ]
            ],
            [
                'title' => 'Übung 5: Genusbestimmung (Noun Gender) - Niveau: Leicht',
                'type' => 'noun_gender',
                'instructions' => 'Ergänzen Sie den bestimmten Artikel (der/die/das).',
                'questions' => [
                    '___ Auto',
                    '___ Mann',
                    '___ Frau'
                ],
                'answers' => ['das', 'der', 'die'],
                'solutions_arabic' => [
                    'كلمة Auto محايدة وأداتها das.',
                    'كلمة Mann مذكرة وأداتها der.',
                    'كلمة Frau مؤنثة وأداتها die.'
                ]
            ],
            [
                'title' => 'Übung 6: Pluralbildung (Plural Formation) - Niveau: Leicht',
                'type' => 'plural_formation',
                'instructions' => 'Schreiben Sie die Pluralform.',
                'questions' => [
                    'das Buch -> die ___',
                    'der Tisch -> die ___',
                    'die Hand -> die ___'
                ],
                'answers' => ['Bücher', 'Tische', 'Hände'],
                'solutions_arabic' => [
                    'جمع كلمة Buch هو Bücher مع إضافة أوملاوت واللاحقة -er.',
                    'جمع كلمة Tisch هو Tische بإضافة اللاحقة -e.',
                    'جمع كلمة Hand هو Hände مع أوملاوت واللاحقة -e.'
                ]
            ],
            [
                'title' => 'Übung 7: Verbkonjugation (Verb Conjugation) - Niveau: Leicht',
                'type' => 'verb_conjugation',
                'instructions' => 'Konjugieren Sie das Verb im Präsens.',
                'questions' => [
                    'ich [helfen]',
                    'du [geben]',
                    'er [sehen]'
                ],
                'answers' => ['helfe', 'gibst', 'sieht'],
                'solutions_arabic' => [
                    'تصريف الفعل helfen مع الضمير ich ينتهي بـ e.',
                    'الفعل geben تتغير فيه حركة الحرف من e إلى i مع الضمير du.',
                    'الفعل sehen تتغير فيه حركة الحرف من e إلى ie مع الضمير er.'
                ]
            ],
            [
                'title' => 'Übung 8: Präpositionsauswahl (Preposition Selection) - Niveau: Mittel',
                'type' => 'preposition_selection',
                'instructions' => 'Wählen Sie die richtige Präposition.',
                'questions' => [
                    'Wir fahren ___ dem Auto (mit/für/über).',
                    'Das Geschenk ist ___ dich (für/mit/zu).',
                    'Sie geht ___ der Arbeit (zu/nach/aus).'
                ],
                'answers' => ['mit', 'für', 'zu'],
                'solutions_arabic' => [
                    'حرف الجر mit يعبر عن استخدام وسيلة النقل ويتطلب Dativ.',
                    'حرف الجر für يعني "لأجل" ويتطلب Akkusativ مفعول به منصوب.',
                    'حرف الجر zu يعني "إلى" (للأشخاص أو العمل) ويتطلب Dativ.'
                ]
            ],
            [
                'title' => 'Übung 9: Satzordnung (Put elements in the correct order) - Niveau: Mittel',
                'type' => 'sentence_ordering',
                'instructions' => 'Bringen Sie die Wörter in die richtige Reihenfolge.',
                'questions' => [
                    'hilft / dem / Mann / er',
                    'Auto / wir / fahren / mit / dem',
                    'gehört / das / Kind / dem / Buch'
                ],
                'answers' => [
                    'Er hilft dem Mann.',
                    'Wir fahren mit dem Auto.',
                    'Das Buch gehört dem Kind.'
                ],
                'solutions_arabic' => [
                    'الفاعل أولاً، ثم الفعل المصرف، ثم المفعول به في حالة الجر Dativ.',
                    'الفعل في الموضع الثاني، وتأتي شبه الجملة بالجر بعدها.',
                    'الفعل "gehören" يتطلب Dativ للمالك.'
                ]
            ],
            [
                'title' => 'Übung 10: Vervollständigung (Complete the missing parts) - Niveau: Mittel',
                'type' => 'complete_missing',
                'instructions' => 'Vervollständigen Sie die fehlenden Präpositionen und Artikel.',
                'questions' => [
                    'Ich gehe ___ Schule.',
                    'Er wohnt ___ dem Bahnhof (gegenüber).',
                    'Wir sprechen ___ dem Lehrer (mit).'
                ],
                'answers' => ['zur', 'gegenüber', 'mit'],
                'solutions_arabic' => [
                    'حرف الجر zu + der أداة التعريف للمؤنث Schule يدمجان ليصبحا "zur".',
                    'حرف الجر gegenüber يعني مقابل ويتطلب Dativ ويقع غالباً بعد الاسم.',
                    'حرف الجر mit يعني مع ويتطلب حالة Dativ.'
                ]
            ],
            [
                'title' => 'Übung 11: Übersetzung (Deutsch nach Arabisch) - Niveau: Mittel',
                'type' => 'translation_de_to_ar',
                'instructions' => 'Übersetzen Sie ins Arabische.',
                'questions' => [
                    'Ich trinke einen Kaffee.',
                    'Das Kind schläft.',
                    'Wir lernen Deutsch.'
                ],
                'answers' => [
                    'أشرب قهوة.',
                    'الطفل ينام.',
                    'نحن نتعلم الألمانية.'
                ],
                'solutions_arabic' => [
                    'ترجمة صحيحة لقهوة منصوبة.',
                    'ترجمة دقيقة للجملة الاسمية البسيطة.',
                    'ترجمة صحيحة تعبر عن الاستمرارية والتعلم.'
                ]
            ],
            [
                'title' => 'Übung 12: Übersetzung (Arabisch nach Deutsch) - Niveau: Mittel',
                'type' => 'translation_ar_to_de',
                'instructions' => 'Übersetzen Sie ins Deutsche.',
                'questions' => [
                    'أنا أساعد أمي.',
                    'السيارة جديدة.',
                    'نحن نذهب إلى المدرسة.'
                ],
                'answers' => [
                    'Ich helfe meiner Mutter.',
                    'Das Auto ist neu.',
                    'Wir gehen zur Schule.'
                ],
                'solutions_arabic' => [
                    'استخدام Dativ للمؤنث (meiner Mutter) بعد helfen.',
                    'صياغة جملة اسمية بسيطة باستخدام sein.',
                    'دمج zu + der في zur لأن المدرسة مؤنث.'
                ]
            ],
            [
                'title' => 'Übung 13: Pronomenersatz (Pronoun Replacement) - Niveau: Mittel',
                'type' => 'pronoun_replacement',
                'instructions' => 'Ersetzen Sie das Nomen durch ein Personalpronomen.',
                'questions' => [
                    'Ich helfe dem Vater -> Ich helfe ___ .',
                    'Ich liebe die Mutter -> Ich liebe ___ .',
                    'Das gehört dem Kind -> Das gehört ___ .'
                ],
                'answers' => ['ihm', 'sie', 'ihm'],
                'solutions_arabic' => [
                    'الاسم المذكر في Dativ (dem Vater) يستبدل بالضمير ihm.',
                    'الاسم المؤنث in Akkusativ (die Mutter) يستبدل بالضمير sie.',
                    'الاسم المحايد في Dativ (dem Kind) يستبدل بالضمير ihm.'
                ]
            ],
            [
                'title' => 'Übung 14: Negation (Negation) - Niveau: Mittel',
                'type' => 'negation',
                'instructions' => 'Verneinen Sie den Satz.',
                'questions' => [
                    'Ich habe ein Auto -> Ich habe ___ Auto.',
                    'Ich komme heute -> Ich komme heute ___ .',
                    'Sie hat eine Frage -> Sie hat ___ Frage.'
                ],
                'answers' => ['kein', 'nicht', 'keine'],
                'solutions_arabic' => [
                    'نفي الأسماء النكرة المسبوقة بـ ein يكون بـ kein للمحايد.',
                    'نفي الأفعال أو ظروف الزمان والمكان يكون بـ nicht.',
                    'نفي المؤنث المسبوق بـ eine يكون بـ keine.'
                ]
            ],
            [
                'title' => 'Übung 15: Adjektivdeklination (Adjective Declination) - Niveau: Schwer',
                'type' => 'adjective_declination',
                'instructions' => 'Ergänzen Sie die Adjektivendung.',
                'questions' => [
                    'mit dem neu___ Auto',
                    'ein gut___ Freund',
                    'schön___ Blumen (Plural ohne Artikel)'
                ],
                'answers' => ['en', 'er', 'e'],
                'solutions_arabic' => [
                    'الصفة بعد الأداة المعرفة في حالة الجر Dativ تنتهي بـ -en دائماً.',
                    'الصفة بعد أداة النكرة ein للمذكر المرفوع تنتهي بـ -er.',
                    'الصفة للجمع بدون أداة تعريف في حالة الرفع تنتهي بـ -e.'
                ]
            ],
            [
                'title' => 'Übung 16: Satzumformung (Rewrite or transform sentences) - Niveau: Schwer',
                'type' => 'sentence_transformation',
                'instructions' => 'Formen Sie den Satz wie vorgegeben um.',
                'questions' => [
                    'Aktiv: Der Mann hilft dem Kind. -> Passiv: Dem Kind ___ .',
                    'Direkte Rede: Er sagt: "Ich bin müde." -> Indirekte Rede: Er sagt, dass ...',
                    'Zwei Sätze: Ich bin krank. Ich bleibe zu Hause. -> Verbinden mit "weil": ...'
                ],
                'answers' => [
                    'wird von dem Mann geholfen',
                    'er müde sei / ist',
                    'Ich bleibe zu Hause, weil ich krank bin.'
                ],
                'solutions_arabic' => [
                    'صياغة المبني للمجهول تتطلب الفعل wird + اسم المفعول geholfen + شبه جملة von + Dativ.',
                    'صياغة الكلام غير المباشر في الألمانية تستعمل Konjunktiv I أو الحاضر مع dass.',
                    'الرابط weil يرسل الفعل المصرف إلى نهاية الجملة.'
                ]
            ],
            [
                'title' => 'Übung 17: Situative Antwort (Write answer based on situation) - Niveau: Schwer',
                'type' => 'situational_response',
                'instructions' => 'Schreiben Sie eine passende Antwort für die gegebene Situation.',
                'questions' => [
                    'Ein Freund dankt dir für die Hilfe. Was antwortest du höflich?',
                    'Du möchtest nach dem Weg zum Bahnhof fragen. Was sagst du?',
                    'Du stimmst einem Vorschlag vollkommen zu. Was sagst du?'
                ],
                'answers' => [
                    'Gern geschehen! / Keine Ursache!',
                    'Entschuldigung, wie komme ich zum Bahnhof?',
                    'Das ist eine tolle Idee! / Einverstanden!'
                ],
                'solutions_arabic' => [
                    'الرد المناسب عند تلقي الشكر هو أهلاً بك أو لا شكر على واجب.',
                    'صيغة السؤال المهذب للغرباء تبدأ بـ Entschuldigung.',
                    'الموافقة التامة يعبر عنها بكلمات تشجيعية أو إيجابية.'
                ]
            ],
            [
                'title' => 'Übung 18: Fehlerkorrektur (Correct the mistakes) - Niveau: Schwer',
                'type' => 'error_correction',
                'instructions' => 'Finden und korrigieren Sie den Grammatikfehler.',
                'questions' => [
                    'Ich helfe den Mann.',
                    'Das ist ein gutes Mann.',
                    'Wir wohnen in das Haus.'
                ],
                'answers' => [
                    'Ich helfe dem Mann.',
                    'Das ist ein guter Lehrer.',
                    'Wir wohnen in dem Haus.'
                ],
                'solutions_arabic' => [
                    'الخطأ den والصواب dem لأن helfen يحتاج Dativ.',
                    'الخطأ gutes والصواب guter لأن Mann مذكر مرفوع.',
                    'الخطأ das والصواب dem لأن السكون في المكان يحتاج Dativ.'
                ]
            ],
            [
                'title' => 'Übung 19: Fehlerdiagnose (Debugging exercises) - Niveau: Schwer',
                'type' => 'debugging',
                'instructions' => 'Diagnostizieren Sie die Fehler in den Sätzen und korrigieren Sie diese.',
                'questions' => [
                    'Ich habe gestern ein neu Auto kauft.',
                    'Weil ich bin krank, ich bleibe zu Hause.',
                    'Er hat sich an die Prüfung vorbereitet.'
                ],
                'answers' => [
                    'Ich habe gestern ein neues Auto gekauft.',
                    'Weil ich krank bin, bleibe ich zu Hause.',
                    'Er hat sich auf die Prüfung vorbereitet.'
                ],
                'solutions_arabic' => [
                    'يوجد خطآن: نهاية الصفة المفعولية Neues والفعل المساعد المدمج gekauft.',
                    'ترتيب الكلمات في جملة weil يرسل الفعل للأخير، وجملة جواب الشرط تبدأ بالفعل.',
                    'الفعل المنعكس sich vorbereiten يأخذ حرف الجر auf وليس an.'
                ]
            ],
            [
                'title' => 'Übung 20: Komplexe Problemlösung (Long problem-solving) - Niveau: Schwer',
                'type' => 'problem_solving',
                'instructions' => 'Lösen Sie die komplexe grammatikalische Aufgabe.',
                'questions' => [
                    'Schreibe eine E-Mail-Entschuldigung an deinen Lehrer (30 Wörter, Dativ-Präpositionen nutzen).',
                    'Erkläre den Unterschied zwischen "antworten" und "beantworten" anhand von Beispielsätzen.',
                    'Bilde einen komplexen Satz mit "obwohl" und "wegen dem/des" zum Thema Regen.'
                ],
                'answers' => [
                    'Sehr geehrter Herr..., ich kann heute nicht zum Unterricht kommen, weil ich mit dem Kind zum Arzt gehen muss...',
                    'antworten + Dativ (ich antworte dir); beantworten + Akkusativ (ich beantworte die Frage).',
                    'Obwohl es stark regnet, gehe ich wegen des Termins nach draußen.'
                ],
                'solutions_arabic' => [
                    'صياغة بريد رسمي تتطلب التحية الرسمية وصيغة التبرير المهذبة مع استخدام حروف الجر.',
                    'الفرق الجوهري هو تعدي الفعل beantworten للمفعول به المباشر ولزوم antworten لحالة الجر.',
                    'صياغة جملة معقدة تربط التناقض والسببية معاً.'
                ]
            ],
        ];

        $flatExercises = array_merge(...array_map(fn ($group) => $group['questions'], $exerciseGroups));
        $flatSolutions = array_merge(...array_map(fn ($group) => $group['solutions_arabic'], $exerciseGroups));

        return [
            'title' => 'Fokus Lektion: ' . $topicLabel,
            'level' => 'A2-B1',
            'topic' => $topicLabel,
            'lesson' => $lessonText,
            'learning_objectives' => $learningObjectives,
            'grammar_points' => $grammarPoints,
            'grammar_tips' => $grammarTips,
            'memory_tricks' => $memoryTricks,
            'common_mistakes' => $commonMistakes,
            'usage_notes' => $usageNotes,
            'vocabulary' => $vocabulary,
            'examples' => $examples,
            'sentences' => $examples,
            'questions' => [
                'Wann benutzt man den Dativ nach Verben?',
                'Welche Präpositionen verlangen immer den Dativ?',
                'Was ist der Unterschied zwischen Nominativ und Akkusativ?',
                'Welche Endung bekommt ein Adjektiv im Dativ Plural nach bestimmten Artikeln?',
                'Wie lautet die Eselsbrücke für Dativ-Präpositionen?'
            ],
            'answers' => [
                'Nach bestimmten Verben des Gebens, Helfens oder Gehörens.',
                'Präpositionen wie mit, nach, von, zu, seit, aus und bei.',
                'Nominativ bezeichnet das Subjekt, Akkusativ das direkte Objekt.',
                'Die Endung ist immer "-en".',
                '"Mit, nach, von, seit, zu, aus, bei - verlangen Dativ, das ist keine Hexerei!"'
            ],
            'exercise_groups' => $exerciseGroups,
            'exercises' => $flatExercises,
            'solutions_arabic' => $flatSolutions,
            'summary' => 'Zusammenfassend lässt sich sagen, dass die korrekte Bestimmung von Genus und Kasus das Fundament der deutschen Deklination bildet. Üben Sie regelmäßig mit den Beispielsätzen und überprüfen Sie Ihre Antworten anhand der detaillierten Lösungen am Ende des Hefts.'
        ];
    }

    private function tryParseJsonString(string $value): ?array
    {
        $value = trim($value);
        $value = preg_replace('/^```(?:json)?\s*/i', '', $value);
        $value = preg_replace('/\s*```$/', '', $value);

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

    public function generate(string $topic): array
    {
        $prompt = <<<'PROMPT'
You are an expert German language curriculum designer (Goethe A1–C2) and German linguist.

====================================================
STEP 1 — TOPIC ANALYSIS
====================================================

Before generating anything, classify the lesson topic into ONE category:
Alphabet | Pronunciation | Vocabulary | Grammar | Verbs | Cases | Tenses | Modal Verbs | Articles | Adjectives | Prepositions | Sentence Structure | Listening | Reading | Conversation | Writing | Exam Preparation | Culture | Idioms | Numbers | Time | Travel | Food | Daily Life | Other

Identify ALL concepts that belong to the requested topic.
Identify all concepts that do NOT belong — these are FORBIDDEN from the lesson.

====================================================
STEP 2 — LESSON BOUNDARIES
====================================================

Create a strict content boundary for the lesson.
ONLY concepts that directly teach the requested topic may appear.
NEVER include concepts from other topics to "fill space."

Examples of enforcement:
- Topic "Das Alphabet" → ONLY letters, umlauts, pronunciation of letters, spelling exercises. FORBIDDEN: Dativ, Akkusativ, verb conjugation.
- Topic "Dativ" → ONLY Dativ rules, Dativ articles, Dativ pronouns, Dativ prepositions. FORBIDDEN: Alphabet, food vocabulary, numbers.
- Topic "Perfekt" → ONLY Perfekt formation, haben/sein selection, past participles. FORBIDDEN: Präteritum, Konjunktiv, Passiv.
- Topic "Modalverben" → ONLY modal verbs and their uses. FORBIDDEN: cases, adjective endings, relative clauses.

====================================================
STEP 3 — GENERATE CONTENT
====================================================

Generate every section ONLY from the approved concepts within the lesson boundary.

All content — vocabulary, examples, exercises, questions, answers, translations — must stay strictly inside the topic boundary.

Output format: a single valid JSON object with NO markdown, NO comments, NO text outside the JSON.

JSON structure:
{
    "title": "Academic German title for the lesson, e.g. 'Das Perfekt: Bildung und Verwendung'",
    "level": "CEFR level, e.g. A2-B1",
    "topic": "Exact topic name",
    "lesson": "Concise but complete grammar explanation (under 300 words). Use bullet points and clear sections. Explain ONLY what belongs to this topic.",
    "learning_objectives": [
        "Learning objective 1 in German (specific to this topic only)",
        "Learning objective 2 in German",
        "Learning objective 3 in German"
    ],
    "grammar_points": [
        "Core rule 1 — directly about the requested topic",
        "Core rule 2 — directly about the requested topic",
        "Core rule 3 — directly about the requested topic",
        "Core rule 4 — directly about the requested topic",
        "Core rule 5 — directly about the requested topic"
    ],
    "grammar_tips": [
        "Practical tip 1 specific to this topic (German + Arabic support)",
        "Practical tip 2 specific to this topic"
    ],
    "memory_tricks": [
        "Memory trick 1 (Eselsbrücke) to remember a key rule of this topic",
        "Memory trick 2 (Eselsbrücke) — topic-specific only"
    ],
    "common_mistakes": [
        {
            "mistake": "Wrong usage — must be a real error specific to this topic",
            "correction": "The correct version",
            "explanation_arabic": "Arabic explanation of why this mistake happens with this topic"
        },
        {
            "mistake": "Another real error specific to this topic",
            "correction": "The correct version",
            "explanation_arabic": "Arabic explanation specific to this topic"
        },
        {
            "mistake": "A third real error specific to this topic",
            "correction": "The correct version",
            "explanation_arabic": "Arabic explanation specific to this topic"
        }
    ],
    "usage_notes": [
        "Usage note 1 — context where this topic is used (formal/informal/spoken/written)",
        "Usage note 2 — another context specific to this topic"
    ],
    "vocabulary": [
        {
            "german": "Word directly related to this topic (with gender/plural for nouns)",
            "arabic": "Precise Arabic meaning",
            "example": "Short German sentence using this word IN THE CONTEXT of the topic",
            "pronunciation": "Short pronunciation tip"
        }
    ],
    "examples": [
        "German sentence that DEMONSTRATES the topic rule — Arabic translation (Arabic explanation of which rule applies)",
        "Another sentence demonstrating the topic — Arabic translation (Arabic explanation)"
    ],
    "sentences": [
        "German sentence demonstrating the topic — Arabic translation (Arabic explanation)",
        "Another sentence demonstrating the topic — Arabic translation (Arabic explanation)"
    ],
    "questions": [
        "Review question about THIS topic specifically (German or Arabic)",
        "Review question 2",
        "Review question 3",
        "Review question 4",
        "Review question 5"
    ],
    "answers": [
        "Answer 1",
        "Answer 2",
        "Answer 3",
        "Answer 4",
        "Answer 5"
    ],
    "exercise_groups": [
        {
            "title": "Übung 1: [Exercise Type] — [Topic Name] — Niveau: Leicht",
            "type": "fill_blank",
            "instructions": "Instructions in German — must describe an exercise about THIS topic only",
            "questions": [
                "Question 1 — tests knowledge of THIS topic only",
                "Question 2 — tests knowledge of THIS topic only",
                "Question 3 — tests knowledge of THIS topic only"
            ],
            "answers": [
                "Answer 1",
                "Answer 2",
                "Answer 3"
            ],
            "solutions_arabic": [
                "Brief Arabic explanation (max 12 words) — why this answer is correct for THIS topic",
                "Brief Arabic explanation for question 2",
                "Brief Arabic explanation for question 3"
            ]
        }
    ],
    "summary": "Summary in German with Arabic support — summarizes ONLY what was taught in this topic. No mention of other grammar topics."
}

====================================================
STEP 4 — CONSISTENCY CHECK
====================================================

After generating each exercise group, verify:
"Does every question in this group teach ONLY the requested topic?"

If NO → delete and regenerate.

Repeat until every exercise belongs 100% to the lesson topic.

====================================================
STEP 5 — FINAL VALIDATION
====================================================

Before returning the JSON, verify each section:

- Vocabulary: 100% relevant to topic
- Examples: 100% demonstrate the topic rule
- Exercises: 100% test the topic — zero mixing with other grammar
- Grammar points: 100% about this topic
- Questions: 100% about this topic
- Summary: 100% summarizes this topic

If ANY item strays from the topic → delete it and replace with a topic-specific one.

====================================================
CRITICAL RULES
====================================================

1. NEVER use generic grammar templates. Build every exercise uniquely for the requested topic.
2. NEVER reuse content from a previous lesson.
3. NEVER fill a lesson with Dativ/Akkusativ examples unless the topic IS Dativ or Akkusativ.
4. The lesson title defines the ENTIRE content. If it says "Das Alphabet" → every single word, sentence, and exercise teaches ONLY the alphabet.
5. Exercise types must be chosen to FIT the topic. Not all 20 types work for every topic — select the ones that are most natural for this specific topic.
6. Generate EXACTLY 15 vocabulary items. Each example sentence max 6 words.
7. Generate EXACTLY 10 example sentences. Put the same array in both "examples" and "sentences".
8. Generate EXACTLY 20 exercise groups, each with EXACTLY 3 questions (60 total).
9. Exercise groups must go from easy (groups 1-7) → medium (groups 8-14) → difficult (groups 15-20).
10. Arabic explanations in solutions_arabic: max 12 words each. Brief, direct, on-topic.
11. No placeholders ("...", "usw.", "etc."). Every field must be fully realized.
12. Return ONLY valid JSON. No markdown. No text before or after.
PROMPT;

        $primaryKey = env('NVIDIA_API_KEY');
        $fallbackKey = env('NVIDIA_API_KEY_OLD');

        $anthropicKey = null;
        $nvidiaKey = null;

        if (is_string($primaryKey) && str_starts_with($primaryKey, 'sk-ant-')) {
            $anthropicKey = $primaryKey;
        } elseif (is_string($primaryKey) && str_starts_with($primaryKey, 'nvapi-')) {
            $nvidiaKey = $primaryKey;
        }

        if (is_string($fallbackKey) && str_starts_with($fallbackKey, 'sk-ant-')) {
            $anthropicKey = $fallbackKey;
        } elseif (is_string($fallbackKey) && str_starts_with($fallbackKey, 'nvapi-')) {
            $nvidiaKey = $fallbackKey;
        }

        if (!$anthropicKey && !$nvidiaKey) {
            Log::error("No valid Anthropic (sk-ant-) or NVIDIA (nvapi-) API keys found in .env.");
            return $this->normalizeLessonPayload($this->buildFallbackLesson($topic));
        }

        $maxAttempts = 2;
        $attempt = 0;
        $decoded = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            
            // 1. Try Anthropic Claude if key is available
            if ($anthropicKey) {
                try {
                    Log::info("Attempting AI generation using Anthropic Claude (Attempt {$attempt})...");
                    $response = Http::timeout(240)
                        ->connectTimeout(20)
                        ->withHeaders([
                            'x-api-key' => $anthropicKey,
                            'anthropic-version' => '2023-06-01',
                            'Content-Type' => 'application/json',
                        ])->post('https://api.anthropic.com/v1/messages', [
                            'model' => 'claude-sonnet-4-5-20250929',
                            'system' => $prompt,
                            'messages' => [
                                [
                                    'role' => 'user',
                                    'content' => 'Topic: ' . $topic,
                                ],
                            ],
                            'temperature' => 0.2 + ($attempt * 0.1),
                            'max_tokens' => 8000,
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        Log::info("Claude response stats: stop_reason = " . data_get($data, 'stop_reason') . ", usage = " . json_encode(data_get($data, 'usage')));
                        $content = data_get($data, 'content.0.text');
                        if (is_string($content) && trim($content) !== '') {
                            Log::info("LLM response received. Length: " . strlen($content));
                            $parsed = $this->tryParseJsonString($content);
                            if (!is_array($parsed)) {
                                $parsed = $this->extractFirstJsonObject($content);
                            }

                            if (is_array($parsed)) {
                                Log::info("JSON parsing succeeded. Checking validatePayload...");
                                if ($this->validatePayload($parsed)) {
                                    $decoded = $parsed;
                                    break;
                                } else {
                                    Log::warning("Anthropic Claude attempt {$attempt} failed validation constraints.");
                                }
                            } else {
                                Log::warning("Anthropic Claude attempt {$attempt} failed to parse JSON.");
                            }
                        }
                    } else {
                        Log::warning("Anthropic Claude attempt {$attempt} failed HTTP status: " . $response->status() . " Body: " . $response->body());
                    }
                } catch (Throwable $e) {
                    Log::warning("Anthropic Claude attempt {$attempt} threw exception: " . $e->getMessage());
                }
            }

            // 2. Fallback to NVIDIA if Anthropic failed or not configured, and NVIDIA key is available
            if (!is_array($decoded) && $nvidiaKey) {
                try {
                    Log::info("Attempting AI fallback generation using NVIDIA (Attempt {$attempt})...");
                    $response = Http::timeout(60)
                        ->connectTimeout(10)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $nvidiaKey,
                            'Content-Type' => 'application/json',
                        ])->post('https://integrate.api.nvidia.com/v1/chat/completions', [
                            'model' => 'meta/llama-3.1-8b-instruct',
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
                            'temperature' => 0.2 + ($attempt * 0.1),
                            'max_tokens' => 4096,
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $content = data_get($data, 'choices.0.message.content');
                        if (is_string($content) && trim($content) !== '') {
                            Log::info("NVIDIA response received. Length: " . strlen($content));
                            $parsed = $this->tryParseJsonString($content);
                            if (!is_array($parsed)) {
                                $parsed = $this->extractFirstJsonObject($content);
                            }

                            if (is_array($parsed)) {
                                Log::info("NVIDIA JSON parsing succeeded. Checking validatePayload...");
                                if ($this->validatePayload($parsed)) {
                                    $decoded = $parsed;
                                    break;
                                } else {
                                    Log::warning("NVIDIA attempt {$attempt} failed validation constraints.");
                                }
                            } else {
                                Log::warning("NVIDIA attempt {$attempt} failed to parse JSON.");
                            }
                        }
                    } else {
                        Log::warning("NVIDIA attempt {$attempt} failed HTTP status: " . $response->status() . " Body: " . $response->body());
                    }
                } catch (Throwable $e) {
                    Log::warning("NVIDIA attempt {$attempt} threw exception: " . $e->getMessage());
                }
            }
        }

        if (!is_array($decoded)) {
            Log::error("All AI generation attempts failed or were invalid. Returning high quality fallback workbook.");
            return $this->normalizeLessonPayload($this->buildFallbackLesson($topic));
        }

        $merged = $this->mergeLessonPayload($this->buildFallbackLesson($topic), $decoded);
        return $this->normalizeLessonPayload($merged);
    }
}