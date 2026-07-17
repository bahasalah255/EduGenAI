<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 50px 45px 50px 45px; }
body { font-family: DejaVu Sans, sans-serif; color: #1a1a2e; font-size: 11px; line-height: 1.65; background: #ffffff; margin: 0; padding: 0; }

/* ── Footer ── */
.doc-footer { position: fixed; bottom: 18px; left: 0; right: 0; text-align: center; font-size: 9px; color: #94a3b8; }
.page-break  { page-break-after: always; }

/* ── Cover ── */
.cover-header {
    background: linear-gradient(135deg, #1a5f7a 0%, #2980b9 60%, #1abc9c 100%);
    border-radius: 10px;
    padding: 36px 30px 30px;
    margin-bottom: 22px;
    color: #ffffff;
}
.cover-header h1 { margin: 0 0 10px; font-size: 26px; font-weight: 900; letter-spacing: -0.3px; }
.cover-header .subtitle { margin: 0; font-size: 13px; opacity: 0.9; direction: rtl; text-align: right; }

/* ── QR dashed box ── */
.qr-box {
    border: 2px dashed #b0c4d8;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 22px;
    display: table;
    width: 100%;
}
.qr-box-inner { display: table-row; }
.qr-left  { display: table-cell; width: 120px; vertical-align: middle; text-align: center; border: 1px solid #b0c4d8; border-radius: 6px; padding: 12px; font-size: 9px; font-weight: 700; color: #2980b9; }
.qr-right { display: table-cell; vertical-align: top; padding-right: 16px; direction: rtl; text-align: right; }
.qr-right strong { color: #1a5f7a; font-size: 12px; }
.qr-right p { margin: 4px 0 0; color: #475569; font-size: 10px; line-height: 1.5; }

/* ── Section headings ── */
.section-title {
    font-size: 16px;
    font-weight: 900;
    color: #1a5f7a;
    direction: rtl;
    text-align: right;
    padding-bottom: 7px;
    border-bottom: 2.5px solid #2980b9;
    margin: 26px 0 14px;
}
.section-title span { color: #2980b9; margin-left: 4px; }

/* ── Color-coded table (declension) ── */
.decl-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.decl-table th { padding: 10px 12px; font-size: 10px; font-weight: 800; color: #fff; text-align: center; }
.decl-table td { padding: 9px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #e2e8f0; }
.decl-table tbody tr:first-child td { font-weight: 500; color: #475569; }
.decl-table tbody tr:last-child  td { font-weight: 800; color: #1a1a2e; }
.th-kasus { background: #64748b; }
.th-mask  { background: #2980b9; }
.th-neut  { background: #1abc9c; }
.th-fem   { background: #e84393; }
.th-plur  { background: #8b5cf6; }

/* ── Cheat sheet callout ── */
.cheat-box {
    background: #fff8e1;
    border-left: 4px solid #f59e0b;
    border-radius: 0 8px 8px 0;
    padding: 12px 16px;
    margin: 18px 0;
    direction: rtl;
    text-align: right;
}
.cheat-box .cheat-title { font-size: 11px; font-weight: 800; color: #b45309; margin-bottom: 4px; }
.cheat-box p { margin: 0; font-size: 11px; color: #78350f; }
.cheat-box strong { color: #1a5f7a; }

/* ── Vocab table (2-col layout) ── */
.vocab-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.vocab-table th { background: #1a5f7a; color: #ffffff; padding: 9px 12px; font-size: 10px; font-weight: 800; text-align: center; }
.vocab-table td { padding: 8px 12px; border-bottom: 1px solid #e8f0f8; font-size: 10.5px; }
.vocab-table tbody tr:nth-child(even) td { background: #f8fbff; }
.vocab-table .ar { direction: rtl; text-align: right; }
.vocab-table .pron { color: #64748b; font-size: 9px; }

/* ── Example sentences ── */
.example-list { margin: 0; padding: 0; list-style: none; }
.example-list li {
    padding: 8px 14px 8px 0;
    border-bottom: 1px dashed #d1e4f0;
    font-size: 11px;
    direction: rtl;
    text-align: right;
}
.example-list li:last-child { border-bottom: none; }
.example-list li .num { color: #2980b9; font-weight: 800; margin-left: 6px; }
.example-list li .hl  { color: #2980b9; font-weight: 800; }

/* ── Q&A cards ── */
.qa-card {
    border-left: 4px solid #2980b9;
    background: #f8fbff;
    border-radius: 0 8px 8px 0;
    padding: 10px 14px;
    margin-bottom: 10px;
}
.qa-card .frage   { font-weight: 700; color: #1a1a2e; }
.qa-card .antwort { font-weight: 400; color: #334155; margin-top: 2px; }
.qa-card .hl      { color: #2980b9; font-weight: 800; }

/* ── Exercise groups ── */
.ex-header {
    background: linear-gradient(90deg, #1a5f7a, #2980b9);
    color: #fff;
    border-radius: 8px 8px 0 0;
    padding: 10px 16px;
    font-size: 12px;
    font-weight: 800;
    direction: rtl;
    text-align: right;
}
.ex-badge {
    display: inline-block;
    background: rgba(255,255,255,0.25);
    border-radius: 999px;
    padding: 2px 10px;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-right: 8px;
    vertical-align: middle;
}
.ex-body { border: 1px solid #cde0f0; border-top: none; border-radius: 0 0 8px 8px; margin-bottom: 16px; }
.ex-instructions { padding: 8px 14px; font-size: 10px; color: #475569; background: #f0f8ff; border-bottom: 1px solid #cde0f0; direction: rtl; text-align: right; }
.ex-row { display: table; width: 100%; border-bottom: 1px dashed #cde0f0; }
.ex-row:last-child { border-bottom: none; }
.ex-num  { display: table-cell; width: 34px; text-align: center; vertical-align: top; padding: 9px 0; color: #2980b9; font-weight: 800; font-size: 10px; }
.ex-q    { display: table-cell; padding: 9px 8px 9px 0; vertical-align: top; font-size: 11px; width: 42%; }
.ex-ans  { display: table-cell; padding: 9px 8px; vertical-align: top; font-size: 11px; font-weight: 800; color: #1a5f7a; width: 14%; }
.ex-sol  { display: table-cell; padding: 9px 8px; vertical-align: top; font-size: 10px; direction: rtl; text-align: right; color: #334155; background: #f8fcff; border-right: 2px solid #2980b9; width: 44%; }

/* ── Solutions ── */
.solution-card { border: 1px solid #d0e8f5; border-radius: 8px; margin-bottom: 12px; page-break-inside: avoid; }
.solution-ans   { background: #1a5f7a; color: #fff; padding: 7px 14px; font-size: 11px; font-weight: 800; border-radius: 7px 7px 0 0; }
.solution-body  { padding: 10px 14px; direction: rtl; text-align: right; font-size: 11px; color: #334155; background: #f8fbff; border-radius: 0 0 7px 7px; }

/* ── Mistakes table ── */
.mistakes-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.mistakes-table th { padding: 9px 12px; font-size: 10px; font-weight: 800; color: #fff; }
.mistakes-table td { padding: 9px 12px; font-size: 10.5px; border-bottom: 1px solid #f0e8e8; vertical-align: top; }
.th-wrong  { background: #dc2626; }
.th-right  { background: #16a34a; }
.th-why    { background: #475569; }
.wrong-cell{ color: #dc2626; font-weight: 700; }
.right-cell{ color: #16a34a; font-weight: 700; }
.ar-cell   { direction: rtl; text-align: right; color: #334155; }

/* ── Grammar tips ── */
.tips-grid { display: table; width: 100%; border-spacing: 0; margin-bottom: 16px; }
.tip-col   { display: table-cell; vertical-align: top; width: 50%; }
.tip-col:first-child { padding-right: 8px; }
.tip-col:last-child  { padding-left: 8px; }
.tip-box   { border-radius: 8px; padding: 12px 14px; height: 100%; }
.tip-orange { background: #fff7ed; border: 1px solid #fed7aa; }
.tip-purple { background: #faf5ff; border: 1px solid #e9d5ff; }
.tip-label  { font-size: 8.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; }
.tip-orange .tip-label { color: #c2410c; }
.tip-purple .tip-label { color: #7c3aed; }
.tip-box ul { margin: 0; padding-right: 16px; }
.tip-box li { font-size: 10px; line-height: 1.5; margin-bottom: 4px; }
.tip-orange li { color: #7c2d12; }
.tip-purple li { color: #4c1d95; }

/* ── Summary ── */
.summary-box { border: 1px dashed #94a3b8; background: #f8fafc; border-radius: 8px; padding: 14px 18px; margin: 16px 0; direction: rtl; text-align: right; }
.summary-box p { margin: 0; font-size: 11px; color: #334155; line-height: 1.7; font-style: italic; }

/* ── Learning objectives ── */
.objectives-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; }
.objectives-box .obj-title { color: #16a34a; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; direction: rtl; text-align: right; }
.objectives-box ul { margin: 0; padding-right: 18px; direction: rtl; text-align: right; }
.objectives-box li { color: #14532d; font-size: 11px; font-weight: 600; margin-bottom: 4px; }

/* ── Usage notes ── */
.usage-list { margin: 0; padding-right: 18px; direction: rtl; text-align: right; }
.usage-list li { margin-bottom: 6px; color: #334155; font-size: 11px; }

/* ── Utilities ── */
.muted    { color: #94a3b8; }
.rtl      { direction: rtl; text-align: right; }
.ltr      { direction: ltr; text-align: left; }
.bold     { font-weight: 800; }
.blue     { color: #2980b9; }
.panel    { border: 1px solid #dbe8f5; border-radius: 10px; padding: 16px 18px; margin-bottom: 16px; background: #fff; page-break-inside: avoid; }
.divider  { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }
</style>
</head>
<body>

<div class="doc-footer">Seite <span style="font-weight:700;">{{ $page ?? '' }}</span></div>

@php
    $t          = $lesson;
    $title      = $t['title']    ?? ($t['topic'] ?? 'Lektion');
    $level      = $t['level']    ?? 'A2-B1';
    $topic      = $t['topic']    ?? $title;
    $lessonText = $t['lesson']   ?? '';
    $gp         = is_array($t['grammar_points'] ?? null) ? $t['grammar_points'] : [];
    $vocab      = is_array($t['vocabulary'] ?? null) ? $t['vocabulary'] : [];
    $examples   = is_array($t['examples'] ?? null) ? ($t['examples'] ?: ($t['sentences'] ?? [])) : ($t['sentences'] ?? []);
    $questions  = is_array($t['questions'] ?? null) ? $t['questions'] : [];
    $answers    = is_array($t['answers'] ?? null) ? $t['answers'] : [];
    $groups     = is_array($t['exercise_groups'] ?? null) ? $t['exercise_groups'] : [];
    $objectives = is_array($t['learning_objectives'] ?? null) ? $t['learning_objectives'] : [];
    $mistakes   = is_array($t['common_mistakes'] ?? null) ? $t['common_mistakes'] : [];
    $usageNotes = is_array($t['usage_notes'] ?? null) ? $t['usage_notes'] : [];
    $gramTips   = is_array($t['grammar_tips'] ?? null) ? $t['grammar_tips'] : [];
    $memTricks  = is_array($t['memory_tricks'] ?? null) ? $t['memory_tricks'] : [];
    $summary    = $t['summary']  ?? '';
    $lessonPara = array_filter(array_map('trim', preg_split('/\n\n+/', trim($lessonText)) ?: []));
@endphp

{{-- ═══════════════════════════════════════ PAGE 1 — COVER ═══════════════════════════════════════ --}}
<div class="cover-header">
    <h1>{{ $title }}</h1>
    <p class="subtitle">Akademisches Arbeitsbuch · Niveau: {{ $level }} · Goethe / TELC / OeSD</p>
</div>

@if(!empty($objectives))
    <div class="objectives-box">
        <div class="obj-title">Lernziele der Lektion</div>
        <ul>
            @foreach($objectives as $obj)
                <li>{{ $obj }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Color-coding declension table --}}
<div class="section-title"><span>1.</span> Farbtabelle der Deklination (Color-Coding)</div>
<p class="rtl muted" style="font-size:10px; margin-bottom:10px;">Die Farben helfen beim schnellen Abrufen der Endungen aus dem Gedaechtnis:</p>

<table class="decl-table">
    <thead>
        <tr>
            <th class="th-kasus">Kasus</th>
            <th class="th-mask">Maskulin (der)</th>
            <th class="th-neut">Neutral (das)</th>
            <th class="th-fem">Feminin (die)</th>
            <th class="th-plur">Plural (die)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Nominativ</td>
            <td>der / ein</td>
            <td>das / ein</td>
            <td>die / eine</td>
            <td>die / –</td>
        </tr>
        <tr>
            <td><strong>Dativ</strong></td>
            <td><strong style="color:#2980b9;">dem / einem</strong></td>
            <td><strong style="color:#1abc9c;">dem / einem</strong></td>
            <td><strong style="color:#e84393;">der / einer</strong></td>
            <td><strong style="color:#8b5cf6;">den / – (+n)</strong></td>
        </tr>
    </tbody>
</table>

@if(!empty($gramTips) || !empty($memTricks))
    <div class="cheat-box">
        <div class="cheat-title">Goldene Grammatikregeln (Cheat Sheet):</div>
        @foreach(array_merge($gramTips, $memTricks) as $tip)
            <p>{{ $tip }}</p>
        @endforeach
    </div>
@endif

{{-- Vocabulary --}}
@if(!empty($vocab))
    <div class="section-title"><span>2.</span> Woerterbuch der Lektion (Vocabulary Glossary)</div>
    <table class="vocab-table">
        <thead>
            <tr>
                <th>Das Wort (German)</th>
                <th>Bedeutung (Arabic)</th>
                <th>Aussprache</th>
                <th>Das Wort (German)</th>
                <th>Bedeutung (Arabic)</th>
            </tr>
        </thead>
        <tbody>
            @php $half = (int)ceil(count($vocab)/2); $left = array_slice($vocab,0,$half); $right = array_slice($vocab,$half); @endphp
            @for($i = 0; $i < $half; $i++)
                <tr>
                    <td class="ltr"><strong>{{ $left[$i]['german'] ?? '' }}</strong></td>
                    <td class="ar">{{ $left[$i]['arabic'] ?? '' }}</td>
                    <td class="pron">{{ $left[$i]['pronunciation'] ?? '' }}</td>
                    <td class="ltr"><strong>{{ $right[$i]['german'] ?? '' }}</strong></td>
                    <td class="ar">{{ $right[$i]['arabic'] ?? '' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>
@endif

<div class="page-break"></div>

{{-- ═══════════════════════════════════════ PAGE 2 — LESSON + EXAMPLES ═══════════════════════════════════════ --}}
@if(!empty($lessonPara))
    <div class="section-title"><span>3.</span> Akademische Grammatikerklarung</div>
    <div class="panel rtl">
        @foreach($lessonPara as $para)
            <p style="margin: 0 0 8px;">{{ $para }}</p>
        @endforeach
    </div>
@endif

@if(!empty($gp))
    <div class="section-title"><span>4.</span> Wichtige Grammatikpunkte</div>
    <div class="panel">
        <ul class="usage-list">
            @foreach($gp as $point)
                <li>{{ $point }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($examples))
    <div class="section-title"><span>5.</span> Anwendungssaetze ({{ count($examples) }} Saetze)</div>
    <ul class="example-list">
        @foreach($examples as $i => $ex)
            <li><span class="num">{{ $i + 1 }}.</span> {{ $ex }}</li>
        @endforeach
    </ul>
@endif

<div class="page-break"></div>

{{-- ═══════════════════════════════════════ PAGE 3 — Q&A ═══════════════════════════════════════ --}}
@if(!empty($questions))
    <div class="section-title"><span>6.</span> Fragen und Antworten ({{ count($questions) }})</div>
    @for($i = 0; $i < count($questions); $i++)
        <div class="qa-card">
            <div class="frage rtl">Frage {{ $i + 1 }}: {{ $questions[$i] }}</div>
            <div class="antwort rtl">↳ {{ $answers[$i] ?? '—' }}</div>
        </div>
    @endfor
@endif

@if(!empty($mistakes))
    <div class="page-break"></div>
    <div class="section-title"><span>7.</span> Typische Fehler und wie man sie vermeidet</div>
    <table class="mistakes-table">
        <thead>
            <tr>
                <th class="th-wrong" style="width:28%">Falsch (Incorrect)</th>
                <th class="th-right" style="width:28%">Richtig (Correct)</th>
                <th class="th-why">Erklaerung auf Arabisch</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mistakes as $cm)
                <tr>
                    <td class="wrong-cell ltr">{{ $cm['mistake'] ?? '' }}</td>
                    <td class="right-cell ltr">{{ $cm['correction'] ?? '' }}</td>
                    <td class="ar-cell">{{ $cm['explanation_arabic'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if(!empty($usageNotes))
    <div class="section-title"><span>8.</span> Wichtige Gebrauchshinweise (Usage Notes)</div>
    <div class="panel">
        <ul class="usage-list">
            @foreach($usageNotes as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="page-break"></div>

{{-- ═══════════════════════════════════════ PAGE 4+ — EXERCISES ═══════════════════════════════════════ --}}
@if(!empty($groups))
    <div class="section-title"><span>9.</span> Aufgaben mit steigendem Schwierigkeitsgrad (Scaffolding Exercises)</div>
    <p class="rtl muted" style="font-size:10px; margin-bottom:14px;">Dieser Abschnitt ist strukturiert von Anfaenger bis Fortgeschrittene:</p>

    @foreach($groups as $gi => $group)
        @php
            $gtitle   = $group['title'] ?? ('Exercise ' . ($gi + 1));
            $gtype    = $group['type']  ?? 'exercise';
            $ginstr   = $group['instructions'] ?? '';
            $gqs      = is_array($group['questions'] ?? null) ? $group['questions'] : [];
            $gas      = is_array($group['answers'] ?? null) ? $group['answers'] : [];
            $gsols    = is_array($group['solutions_arabic'] ?? null) ? $group['solutions_arabic'] : [];
            $gcount   = max(count($gqs), count($gas), count($gsols));
        @endphp

        <div style="margin-bottom: 18px; page-break-inside: avoid;">
            <div class="ex-header">
                <span class="ex-badge">{{ strtoupper($gtype) }}</span>
                {{ $gtitle }}
            </div>
            <div class="ex-body">
                @if($ginstr)
                    <div class="ex-instructions">📋 {{ $ginstr }}</div>
                @endif
                @for($i = 0; $i < $gcount; $i++)
                    <div class="ex-row">
                        <div class="ex-num">{{ $i + 1 }}</div>
                        <div class="ex-q ltr">{{ $gqs[$i] ?? '' }}</div>
                        <div class="ex-ans ltr">{{ $gas[$i] ?? '' }}</div>
                        <div class="ex-sol">{{ $gsols[$i] ?? '' }}</div>
                    </div>
                @endfor
            </div>
        </div>

        @if(($gi + 1) % 3 === 0 && !$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
@endif

{{-- ═══════════════════════════════════════ FINAL PAGE — SUMMARY ═══════════════════════════════════════ --}}
@if($summary)
    <div class="page-break"></div>
    <div class="section-title"><span>10.</span> Zusammenfassung und Abschlussuebersicht</div>
    <div class="summary-box">
        <p>{{ $summary }}</p>
    </div>
@endif

{{-- Notes space --}}
<div class="section-title" style="margin-top: 30px;">Mein persoenlicher Fehler-Notizbereich</div>
<div class="panel" style="min-height: 160px; background: #fafafa;">
    <p class="rtl muted" style="font-size: 10px;">Notiere hier Verben und Praepositionen, die dir schwerfallen, sowie Endungen, bei denen du Fehler gemacht hast.</p>
    @for($i = 0; $i < 8; $i++)
        <hr class="divider">
    @endfor
</div>

</body>
</html>
