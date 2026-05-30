<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 36px 40px; }
        @page {
            @bottom-right {
                content: "Page " counter(page) " / " counter(pages);
                font-size: 9px;
                color: #94a3b8;
            }
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #152033;
            font-size: 11.5px;
            line-height: 1.55;
            background: #ffffff;
        }

        .page-break { page-break-after: always; }

        .doc-header {
            position: fixed;
            top: 8px;
            left: 40px;
            right: 40px;
            height: 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #475569;
        }

        .doc-footer {
            position: fixed;
            bottom: 8px;
            left: 40px;
            right: 40px;
            height: 20px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }

        .cover {
            min-height: 740px;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e6eef9;
            background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
            padding: 28px 36px;
        }

        .cover-band { height: 94px; background: linear-gradient(90deg, #1f3c88 0%, #3c7be0 100%); }
        .cover-inner { padding: 22px 24px 18px; }

        .eyebrow {
            margin: 0;
            color: #dbeafe;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .cover-title {
            margin: 4px 0 6px;
            font-size: 28px;
            line-height: 1.05;
            font-weight: 800;
            color: #0f172a;
        }

        .cover-subtitle {
            margin: 0 0 16px;
            font-size: 12px;
            color: #475569;
        }

        .cover-grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin-bottom: 8px; }
        .cover-card {
            border: 1px solid #dbe4f3;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 12px 14px;
            vertical-align: top;
        }
        .cover-card-label {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .cover-card-value { display: block; font-size: 12px; color: #0f172a; font-weight: 700; }

        .panel {
            margin: 0 0 16px;
            padding: 16px 18px;
            border: 1px solid #dde6f2;
            border-radius: 16px;
            background: #ffffff;
            page-break-inside: avoid;
        }

        .panel-title {
            margin: 0 0 12px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .panel-title.blue { color: #2563eb; }
        .panel-title.green { color: #0f766e; }
        .panel-title.orange { color: #c2410c; }
        .panel-title.purple { color: #7c3aed; }

        .panel-note { margin: 0; color: #334155; white-space: pre-wrap; }

        .mini-table,
        .qa-table,
        .exercise-table { width: 100%; border-collapse: collapse; }
        .mini-table th,
        .mini-table td,
        .qa-table th,
        .qa-table td,
        .exercise-table th,
        .exercise-table td {
            border: 1px solid #e6eef9;
            padding: 10px 12px;
            vertical-align: top;
        }
        .mini-table th,
        .qa-table th,
        .exercise-table th {
            background: linear-gradient(90deg, #f1f8ff, #fbfdff);
            color: #0b1220;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .exercise-table tbody tr:nth-child(odd) { background: #fbfdff; }
        .qa-table tbody tr:nth-child(odd) { background: #fbfdff; }

        .two-col { width: 100%; border-collapse: separate; border-spacing: 12px 0; }
        .two-col td { vertical-align: top; width: 50%; }

        .list { margin: 0; padding-left: 18px; }
        .list li { margin-bottom: 5px; }

        .group-title { margin: 0 0 8px; font-size: 14px; font-weight: 800; color: #0f172a; }

        .solution-box {
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fbff;
            border: 1px solid #dbe4f3;
        }

        .arabic {
            direction: rtl;
            text-align: right;
            font-family: DejaVu Sans, sans-serif;
        }

        .cover-meta { color: #334155; font-size: 12px; margin-top: 6px; }

        .cover-emblem {
            float: right;
            width: 72px;
            height: 72px;
            border-radius: 8px;
            background: linear-gradient(90deg,#1d4ed8,#3b82f6);
            color: #fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:800;
            font-size:18px;
        }

        .muted { color: #64748b; }

        .color-tags { width: 100%; border-collapse: separate; border-spacing: 8px; }
        .color-tags td {
            width: 25%;
            border-radius: 999px;
            color: #ffffff;
            padding: 8px 12px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
        }
        .tag-green { background: linear-gradient(90deg, #0f766e, #14b8a6); }
        .tag-blue { background: linear-gradient(90deg, #1d4ed8, #3b82f6); }
        .tag-orange { background: linear-gradient(90deg, #c2410c, #f97316); }
        .tag-purple { background: linear-gradient(90deg, #6d28d9, #8b5cf6); }

        .footer-hint {
            margin-top: 10px;
            color: #64748b;
            font-size: 9px;
            text-align: center;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <div class="doc-header__left">
            <strong>{{ $topic }}</strong>
        </div>
        <div class="doc-header__right muted">Generated: {{ date('Y-m-d') }}</div>
    </div>
    <div class="doc-footer">&nbsp;</div>
    @php
        $topic = $lesson['topic'] ?? 'Lesson';
        $lessonText = $lesson['lesson'] ?? 'No lesson text provided.';
        $sentences = is_array($lesson['sentences'] ?? null) ? $lesson['sentences'] : [];
        $questions = is_array($lesson['questions'] ?? null) ? $lesson['questions'] : [];
        $answers = is_array($lesson['answers'] ?? null) ? $lesson['answers'] : [];
        $groups = is_array($lesson['exercise_groups'] ?? null) ? $lesson['exercise_groups'] : [];
        $groupCount = count($groups);
        $sentenceCount = count($sentences);
        $questionCount = count($questions);
        $answerCount = count($answers);
        $lessonLines = preg_split('/\n\n+|\r\n\r\n+|\r\r+/', trim($lessonText)) ?: [];
        $lessonSummary = array_slice(array_filter(array_map('trim', $lessonLines)), 0, 3);
        $quickNotes = [
            'Topic: ' . $topic,
            'Lesson language: German',
            'Sentences: ' . $sentenceCount,
            'Questions: ' . $questionCount,
            'Answers: ' . $answerCount,
            'Exercise groups: ' . $groupCount,
        ];
        $glossaryRows = [];
        if ($topic) { $glossaryRows[] = [$topic, 'الموضوع / الدرس']; }
        if (!empty($sentences[0])) { $glossaryRows[] = [$sentences[0], 'جملة مثال أولى']; }
        if (!empty($questions[0])) { $glossaryRows[] = [$questions[0], 'أول سؤال']; }
        if (!empty($answers[0])) { $glossaryRows[] = [$answers[0], 'أول جواب']; }
        foreach ($groups as $group) {
            if (!empty($group['group_title'])) { $glossaryRows[] = [$group['group_title'], 'مجموعة تمارين']; break; }
        }
        $glossaryRows = array_slice($glossaryRows, 0, 5);
    @endphp

    <div class="cover">
        <div class="cover-band">
            <div style="padding: 16px 22px 0;">
                <p class="eyebrow">{{ strtoupper($topic) }}</p>
            </div>
        </div>
        <div class="cover-inner">
            <h1 class="cover-title">{{ $topic }}</h1>
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="flex:1;">
                    <p class="cover-subtitle">Intensive German lesson PDF with academic layout, grouped exercises, and Arabic explanations.</p>
                    <p class="cover-meta">Author: Auto-generated • Level: A1-B1 • {{ date('Y') }}</p>
                </div>
                <div style="width:100px; text-align:right;">
                    <div class="cover-emblem">DE</div>
                </div>
            </div>

            <table class="cover-grid">
                <tr>
                    <td class="cover-card"><span class="cover-card-label">Sentences</span><span class="cover-card-value">{{ $sentenceCount }}</span></td>
                    <td class="cover-card"><span class="cover-card-label">Questions</span><span class="cover-card-value">{{ $questionCount }}</span></td>
                    <td class="cover-card"><span class="cover-card-label">Answers</span><span class="cover-card-value">{{ $answerCount }}</span></td>
                </tr>
                <tr>
                    <td class="cover-card"><span class="cover-card-label">Groups</span><span class="cover-card-value">{{ $groupCount }}</span></td>
                    <td class="cover-card"><span class="cover-card-label">Difficulty</span><span class="cover-card-value">A1-B1</span></td>
                    <td class="cover-card"><span class="cover-card-label">Format</span><span class="cover-card-value">Academic PDF</span></td>
                </tr>
            </table>

            <div class="panel">
                <p class="panel-title blue">Color-Coding</p>
                <table class="color-tags">
                    <tr>
                        <td class="tag-green">Core grammar</td>
                        <td class="tag-blue">Examples</td>
                        <td class="tag-orange">Exercises</td>
                        <td class="tag-purple">Arabic notes</td>
                    </tr>
                </table>
            </div>

            <table class="two-col">
                <tr>
                    <td>
                        <div class="panel">
                            <p class="panel-title green">Cheat Sheet</p>
                            <ul class="list">
                                @foreach($quickNotes as $note)
                                    <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                    <td>
                        <div class="panel">
                            <p class="panel-title purple">Vocabulary Glossary</p>
                            @if(!empty($glossaryRows))
                                <table class="mini-table">
                                    <thead>
                                        <tr>
                                            <th>German</th>
                                            <th>Arabic meaning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($glossaryRows as $row)
                                            <tr>
                                                <td>{{ $row[0] }}</td>
                                                <td class="arabic">{{ $row[1] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="muted">No glossary data available.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer-hint">Page 1 of the lesson export</div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="panel">
        <p class="panel-title blue">Lesson Overview</p>
        @if(!empty($lessonSummary))
            @foreach($lessonSummary as $paragraph)
                <p class="panel-note">{{ $paragraph }}</p>
            @endforeach
        @else
            <p class="panel-note">{{ $lessonText }}</p>
        @endif
    </div>

    <table class="two-col">
        <tr>
            <td>
                <div class="panel">
                    <p class="panel-title green">Sentences</p>
                    @if(!empty($sentences))
                        <ol class="list">
                            @foreach($sentences as $sentence)
                                <li>{{ $sentence }}</li>
                            @endforeach
                        </ol>
                    @else
                        <p class="muted">No sentences provided.</p>
                    @endif
                </div>
            </td>
            <td>
                <div class="panel">
                    <p class="panel-title purple">Questions & Answers</p>
                    @if(!empty($questions) || !empty($answers))
                        <table class="qa-table">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Answer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < max(count($questions), count($answers)); $i++)
                                    <tr>
                                        <td>{{ $questions[$i] ?? '' }}</td>
                                        <td>{{ $answers[$i] ?? '' }}</td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    @else
                        <p class="muted">No questions or answers provided.</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    @if(!empty($groups))
        @foreach($groups as $groupIndex => $group)
            @php
                $exercises = is_array($group['exercises'] ?? null) ? $group['exercises'] : [];
                $solutions = is_array($group['solutions_arabic'] ?? null) ? $group['solutions_arabic'] : [];
                $count = max(count($exercises), count($solutions));
            @endphp

            <div class="panel">
                <p class="panel-title orange">Scaffolding Exercises</p>
                <h2 class="group-title">{{ $group['group_title'] ?? ('Exercise Group ' . ($groupIndex + 1)) }}</h2>

                @if($count > 0)
                    <table class="exercise-table">
                        <thead>
                            <tr>
                                <th style="width:6%">#</th>
                                <th style="width:44%">Exercise</th>
                                <th style="width:50%">Arabic solution and explanation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < $count; $i++)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $exercises[$i] ?? '' }}</td>
                                    <td class="arabic"><div class="solution-box">{{ $solutions[$i] ?? '' }}</div></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                @else
                    <p class="muted">No exercises provided.</p>
                @endif
            </div>

            @if(! $loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endif
</body>
</html>
