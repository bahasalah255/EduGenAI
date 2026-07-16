<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 42px 40px 40px; }
        @page {
            @bottom-right {
                content: "Page " counter(page) " / " counter(pages);
                font-size: 9px;
                color: #6b7280;
            }
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.6;
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
            border-bottom: 1px solid #dbe3ee;
            padding-bottom: 6px;
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
            border: 1px solid #dbe3ee;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 28px 36px;
        }

        .cover-band { height: 18px; background: linear-gradient(90deg, #17324d 0%, #2f5d88 100%); }
        .cover-inner { padding: 30px 24px 18px; }

        .eyebrow {
            margin: 0;
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .cover-title {
            margin: 4px 0 8px;
            font-size: 30px;
            line-height: 1.08;
            font-weight: 800;
            color: #0f172a;
        }

        .cover-subtitle {
            margin: 0 0 18px;
            font-size: 12px;
            color: #334155;
        }

        .cover-grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin-bottom: 12px; }
        .cover-card {
            border: 1px solid #d9e2ec;
            border-radius: 12px;
            background: #ffffff;
            padding: 12px 14px;
            vertical-align: top;
        }
        .cover-card-label {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .cover-card-value { display: block; font-size: 12px; color: #0f172a; font-weight: 700; }

        .meta-strip {
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .meta-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }

        .meta-grid td {
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            padding: 10px 12px;
            background: #ffffff;
            vertical-align: top;
        }

        .meta-label {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-weight: 800;
        }

        .meta-value {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        .panel {
            margin: 0 0 16px;
            padding: 16px 18px;
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            background: #ffffff;
            page-break-inside: avoid;
        }

        .panel-title {
            margin: 0 0 12px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .panel-title.blue { color: #1d4ed8; }
        .panel-title.green { color: #0f766e; }
        .panel-title.orange { color: #b45309; }
        .panel-title.purple { color: #6d28d9; }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin: 0 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-index {
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .section-heading {
            margin: 0;
            font-size: 15px;
            line-height: 1.25;
            color: #0f172a;
            font-weight: 800;
        }

        .section-subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 10px;
        }

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
            background: #f8fafc;
            color: #0b1220;
            font-size: 9px;
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
            background: #f8fafc;
            border: 1px solid #dbe3ee;
        }

        .callout {
            border-left: 3px solid #1d4ed8;
            background: #f8fafc;
            padding: 10px 12px;
            margin-top: 10px;
        }

        .callout-title {
            display: block;
            margin-bottom: 4px;
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
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
            background: linear-gradient(90deg,#17324d,#2f5d88);
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

        .summary-block {
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            padding: 12px 14px;
            background: #ffffff;
            margin-bottom: 12px;
        }

        .summary-list {
            margin: 0;
            padding-left: 18px;
        }

        .summary-list li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    @php
        $topic = $lesson['topic'] ?? 'Lesson';
    @endphp
    <div class="doc-header">
        <div class="doc-header__left">
            <strong>{{ $topic }}</strong>
        </div>
        <div class="doc-header__right muted">Generated: {{ date('Y-m-d') }}</div>
    </div>
    <div class="doc-footer">&nbsp;</div>
    @php
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
                    <p class="cover-subtitle">Academic German as a Foreign Language workbook with structured explanation, correction tables, and Arabic support notes.</p>
                    <p class="cover-meta">Author: VIZARTTES • Level: A2-B1 • {{ date('Y') }}</p>
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
                    <td class="cover-card"><span class="cover-card-label">Difficulty</span><span class="cover-card-value">A2-B1</span></td>
                    <td class="cover-card"><span class="cover-card-label">Format</span><span class="cover-card-value">Academic PDF</span></td>
                </tr>
            </table>

            <div class="meta-strip">
                <table class="meta-grid">
                    <tr>
                        <td><span class="meta-label">Focus</span><span class="meta-value">Clear grammar explanation</span></td>
                        <td><span class="meta-label">Use</span><span class="meta-value">Classroom, self-study, revision</span></td>
                    </tr>
                    <tr>
                        <td><span class="meta-label">Exam Targets</span><span class="meta-value">Goethe, telc, ÖSD</span></td>
                        <td><span class="meta-label">Format</span><span class="meta-value">Printable academic PDF</span></td>
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
                    <div class="section-header">
                        <div>
                            <p class="section-index">Page 2</p>
                            <h2 class="section-heading">Sentences and core examples</h2>
                            <p class="section-subtitle">Short reference sentences for quick review</p>
                        </div>
                    </div>
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
                    <div class="section-header">
                        <div>
                            <p class="section-index">Review</p>
                            <h2 class="section-heading">Questions and answers</h2>
                            <p class="section-subtitle">Fast recall for guided revision</p>
                        </div>
                    </div>
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
                <div class="section-header">
                    <div>
                        <p class="section-index">Practice {{ $groupIndex + 1 }}</p>
                        <h2 class="section-heading">{{ $group['group_title'] ?? ('Exercise Group ' . ($groupIndex + 1)) }}</h2>
                        <p class="section-subtitle">Exercise with correction and Arabic explanation</p>
                    </div>
                </div>

                @if($count > 0)
                    <table class="exercise-table">
                        <thead>
                            <tr>
                                <th style="width:6%">#</th>
                                <th style="width:44%">Exercise</th>
                                <th style="width:50%">Correction and Arabic explanation</th>
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
