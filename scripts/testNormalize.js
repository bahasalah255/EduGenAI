// Quick test harness to run the frontend normalizeResult logic in Node
// Run with: node scripts/testNormalize.js

const cleanText = (value) => {
  if (typeof value !== 'string') return '';
  return value.replace(/\\n/g, '\n').trim();
};

const tryParseJson = (value) => {
  if (typeof value !== 'string') return null;
  try { return JSON.parse(value); } catch {
    try {
      let s = value.trim();
      s = s.replace(/[“”]/g, '"').replace(/[‘’]/g, "'");
      s = s.replace(/'([^']*)'/g, '"$1"');
      s = s.replace(/,\s*([}\]])/g, '$1');
      return JSON.parse(s);
    } catch {
      return null;
    }
  }
};

const extractFirstJsonObject = (text) => {
  if (typeof text !== 'string') return null;
  const start = text.indexOf('{');
  if (start === -1) return null;
  let depth = 0;
  for (let i = start; i < text.length; i += 1) {
    const char = text[i];
    if (char === '{') depth += 1;
    if (char === '}') depth -= 1;
    if (depth === 0) {
      const candidate = text.slice(start, i + 1);
      const parsed = tryParseJson(candidate);
      if (parsed && typeof parsed === 'object') return parsed;
    }
  }
  return null;
};

const toArray = (value) => {
  if (Array.isArray(value)) return value.map((i) => cleanText(String(i ?? ''))).filter(Boolean);
  if (typeof value === 'string') {
    const parsed = tryParseJson(value);
    if (Array.isArray(parsed)) return parsed.map((i) => cleanText(String(i ?? ''))).filter(Boolean);
    return value.split(/\r?\n/).map((l) => l.replace(/^\s*(?:[-*]|\d+[.)])\s*/, '').trim()).filter(Boolean);
  }
  return [];
};

const extractJsonArrayByKey = (lessonText, key) => {
  if (!lessonText) return [];
  let idx = lessonText.indexOf(`"${key}"`);
  if (idx === -1) idx = lessonText.indexOf(`'${key}'`);
  if (idx === -1) idx = lessonText.indexOf(key);
  if (idx === -1) return [];
  const bracketStart = lessonText.indexOf('[', idx);
  if (bracketStart === -1) return [];
  let depth = 0; let endIndex = -1;
  for (let i = bracketStart; i < lessonText.length; i += 1) {
    const ch = lessonText[i];
    if (ch === '[') depth += 1; else if (ch === ']') { depth -= 1; if (depth === 0) { endIndex = i; break; } }
  }
  let snippet = '';
  if (endIndex !== -1) snippet = lessonText.slice(bracketStart, endIndex + 1);
  else snippet = lessonText.slice(bracketStart) + ']';
  const parsed = tryParseJson(snippet);
  if (Array.isArray(parsed)) return parsed.map((it) => cleanText(String(it ?? ''))).filter(Boolean);
  const items = []; const qRe = /["']([^"']{1,}?)["']/g; let m;
  while ((m = qRe.exec(snippet)) !== null) { if (m[1] && m[1].trim()) items.push(cleanText(m[1])); }
  if (items.length === 0) return snippet.split(/\r?\n/).map((l) => l.replace(/^[\s\[\],\d\.\-\*]+/, '').trim()).filter(Boolean);
  return items;
};

const normalizeResult = (result) => {
  if (result == null) return null;
  let normalized = null;
  if (typeof result === 'string') {
    const embedded = extractFirstJsonObject(result);
    normalized = embedded && typeof embedded === 'object' ? { ...embedded } : { lesson: String(result) };
  } else if (typeof result === 'object' && result.data && typeof result.data === 'object') normalized = { ...result.data };
  else if (typeof result === 'object') normalized = { ...result };
  else return null;

  if (typeof normalized.lesson === 'string') {
    const parsedLessonDirect = tryParseJson(normalized.lesson);
    if (parsedLessonDirect && typeof parsedLessonDirect === 'object') normalized = { ...normalized, ...parsedLessonDirect };
    else {
      const unescaped = String(normalized.lesson).replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\'/g, "'");
      const embeddedJson = extractFirstJsonObject(unescaped);
      if (embeddedJson && typeof embeddedJson === 'object') normalized = { ...normalized, ...embeddedJson };
    }
  }

  const rawGroups = normalized.exercise_groups || normalized.exerciseGroups || normalized.groups || null;
  const exercise_groups = Array.isArray(rawGroups) ? rawGroups.map((g) => ({
    group_title: cleanText(String(g.group_title ?? g.groupTitle ?? '')),
    exercises: toArray(g.exercises ?? g.items ?? g.text ?? []),
    solutions_arabic: toArray(g.solutions_arabic ?? g.solutionsArabic ?? g.solutions ?? []),
  })) : [];

  const topExercises = toArray(normalized.exercises);
  const topSolutions = toArray(normalized.solutions_arabic);
  const flatExercises = topExercises.length > 0 ? topExercises : exercise_groups.flatMap((g) => g.exercises || []);
  const flatSolutions = topSolutions.length > 0 ? topSolutions : exercise_groups.flatMap((g) => g.solutions_arabic || []);

  const lessonText = typeof normalized.lesson === 'string' ? normalized.lesson : '';
  const fallbackExercises = flatExercises.length > 0 ? flatExercises : extractJsonArrayByKey(lessonText, 'exercises');
  const fallbackSolutions = flatSolutions.length > 0 ? flatSolutions : (extractJsonArrayByKey(lessonText, 'solutions_arabic').length ? extractJsonArrayByKey(lessonText, 'solutions_arabic') : extractJsonArrayByKey(lessonText, 'solutions'));
  const fallbackExercisesFinal = fallbackExercises.length ? fallbackExercises : extractJsonArrayByKey(lessonText, 'exercises');
  const fallbackSentences = Array.isArray(normalized.sentences) && normalized.sentences.length > 0 ? toArray(normalized.sentences) : extractJsonArrayByKey(lessonText, 'sentences');
  const fallbackQuestions = Array.isArray(normalized.questions) && normalized.questions.length > 0 ? toArray(normalized.questions) : extractJsonArrayByKey(lessonText, 'questions');
  const fallbackAnswers = Array.isArray(normalized.answers) && normalized.answers.length > 0 ? toArray(normalized.answers) : extractJsonArrayByKey(lessonText, 'answers');

  try {
    const needsRecovery = (
      (!fallbackExercises || fallbackExercises.length === 0) ||
      (!fallbackSentences || fallbackSentences.length === 0) ||
      (!fallbackSolutions || fallbackSolutions.length === 0) ||
      (!fallbackQuestions || fallbackQuestions.length === 0) ||
      (!fallbackAnswers || fallbackAnswers.length === 0)
    );
    if (needsRecovery && typeof normalized.lesson === 'string') {
      let inner = tryParseJson(normalized.lesson);
      if (!inner) {
        const unescaped = String(normalized.lesson).replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\'/g, "'");
        inner = extractFirstJsonObject(unescaped) || tryParseJson(unescaped);
      }
      if (inner && typeof inner === 'object') {
        const innerExercises = Array.isArray(inner.exercises) ? inner.exercises.map((it) => cleanText(String(it ?? ''))).filter(Boolean) : [];
        const innerSentences = Array.isArray(inner.sentences) ? inner.sentences.map((it) => cleanText(String(it ?? ''))).filter(Boolean) : [];
        const innerSolutions = Array.isArray(inner.solutions_arabic) ? inner.solutions_arabic.map((it) => cleanText(String(it ?? ''))).filter(Boolean) : (Array.isArray(inner.solutions) ? inner.solutions.map((it) => cleanText(String(it ?? ''))).filter(Boolean) : []);
        const innerQuestions = Array.isArray(inner.questions) ? inner.questions.map((it) => cleanText(String(it ?? ''))).filter(Boolean) : [];
        const innerAnswers = Array.isArray(inner.answers) ? inner.answers.map((it) => cleanText(String(it ?? ''))).filter(Boolean) : [];
        if ((!fallbackExercises || fallbackExercises.length === 0) && innerExercises.length > 0) { fallbackExercises.splice(0, fallbackExercises.length, ...innerExercises); }
        if ((!fallbackSentences || fallbackSentences.length === 0) && innerSentences.length > 0) { fallbackSentences.splice(0, fallbackSentences.length, ...innerSentences); }
        if ((!fallbackSolutions || fallbackSolutions.length === 0) && innerSolutions.length > 0) { fallbackSolutions.splice(0, fallbackSolutions.length, ...innerSolutions); }
        if ((!fallbackQuestions || fallbackQuestions.length === 0) && innerQuestions.length > 0) { fallbackQuestions.splice(0, fallbackQuestions.length, ...innerQuestions); }
        if ((!fallbackAnswers || fallbackAnswers.length === 0) && innerAnswers.length > 0) { fallbackAnswers.splice(0, fallbackAnswers.length, ...innerAnswers); }
        if (typeof inner.lesson === 'string' && inner.lesson.trim()) normalized.lesson = inner.lesson;
      }
    }
  } catch (e) {}

  return {
    topic: cleanText(String(normalized.topic ?? '')),
    lesson: cleanText(String(normalized.lesson ?? '')),
    sentences: fallbackSentences,
    exercises: (fallbackExercises.length ? fallbackExercises : fallbackExercisesFinal),
    solutions_arabic: fallbackSolutions,
    questions: fallbackQuestions,
    answers: fallbackAnswers,
    exercise_groups,
  };
};

// === Test input (copied from your pasted payload) ===
const input = {
  topic: 'Begrüßungen',
  lesson: `{
  "topic": "Begrüßungen",
  "lesson": "Die Begrüßungen sind wichtig im Deutschen. Sie können jemanden mit einem einfachen 'Hallo' begrüßen. Hier sind einige Beispiele: Hallo, wie geht es dir? (مرحبا، كيف حالك؟), Guten Morgen! (صباح الخير!), Guten Abend! (مساء الخير!).",
  "sentences": [
    "Hallo, ich bin Hans. (مرحبا، أنا هانس)",
    "Wie geht es dir? (كيف حالك؟)",
    "Ich bin gut, danke. (أنا بخير، شكراً)",
    "Guten Morgen, wie geht es Ihnen? (صباح الخير، كيف حالكم؟)",
    "Guten Abend, ich freue mich, Sie zu sehen. (مساء الخير، يسعدني رؤيتكم)",
    "Hallo, ich brauche Hilfe. (مرحبا، أنا أحتاج مساعدة)",
    "Guten Tag, ich bin neu hier. (يوم جيد، أنا جديد هنا)",
    "Wie heißen Sie? (ما اسمك؟)",
    "Ich heiße Hans, und Sie? (اسمي هانس، وأنت؟)",
    "Guten Morgen, ich wünsche Ihnen einen schönen Tag. (صباح الخير، أتمنى لكم يوماً جميلاً)"
  ],
  "exercises": [
    "Schreiben Sie eine Begrüßung für einen Freund.",
    "Sagen Sie 'Guten Morgen' auf Deutsch.",
    "Wie begrüßen Sie jemanden, den Sie nicht kennen?",
    "Schreiben Sie eine kurze Geschichte über eine Begrüßung.",
    "Sagen Sie 'Guten Abend' auf Deutsch.",
    "Wie begrüßen Sie einen Lehrer?",
    "Schreiben Sie eine Begrüßung für eine Party.",
    "Sagen Sie 'Hallo' auf Deutsch.",
    "Wie begrüßen Sie jemanden, der krank ist?",
    "Schreiben Sie eine Begrüßung für ein Geschenk.",
    "Sagen Sie 'Guten Tag' auf Deutsch.",
    "Wie begrüßen Sie einen Kollegen?",
    "Schreiben Sie eine Begrüßung für ein Meeting.",
    "Sagen Sie 'Auf Wiedersehen' auf Deutsch."
  ],
  "solutions_arabic": [
    "يمكنك كتابة ببساطة 'مرحبا، كيف حالك؟'",
    "يمكنك قول 'صباح الخير'",
    "يمكنك قول 'مرحبا، أنا ...'",
    "يمكنك كتابة قصة قصيرة عن لقاء مع صديق",
    "يمكنك قول 'مساء الخير'",
    "يمكنك قول 'مرحبا، أستاذ ...'",
    "يمكنك كتابة 'مرحبا، يسعدني رؤيتكم'",
    "يمكنك قول 'مرحبا'",
    "يمكنك قول 'أتمنى لك الشفاء'",
    "يمكنك كتابة 'مرحبا، هذا هدية لك'",
    "يمكنك قول 'يوم جيد'",
    "يمكنك قول 'مرحبا، زميل ...'",
    "يمكنك كتابة 'مرحبا، يسعدني رؤيتكم'",
    "يمكنك قول 'مع السلامة'"
  ],
  "questions": [
    "Wie sagt man 'Guten Morgen' auf Deutsch?",
    "Wie begrüßt man jemanden, den"
  ]
}`,
  sentences: [],
  exercises: [],
  solutions_arabic: [],
  questions: [],
  answers: []
};

const out = normalizeResult(input);
console.log(JSON.stringify(out, null, 2));
