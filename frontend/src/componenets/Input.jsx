import { useMemo, useState } from "react";
import axios from "axios";
import "./Input.css";

const cleanText = (value) => {
  if (typeof value !== "string") {
    return "";
  }

  return value.replace(/\\n/g, "\n").trim();
};

const tryParseJson = (value) => {
  if (typeof value !== "string") {
    return null;
  }

  try {
    return JSON.parse(value);
  } catch {
    // Try a tolerant parse: fix common issues (single quotes, trailing commas)
    try {
      let s = value.trim();

      // Replace smart quotes
      s = s.replace(/[“”]/g, '"').replace(/[‘’]/g, "'");

      // Convert single-quoted strings to double quotes when safe
      s = s.replace(/'([^']*)'/g, '"$1"');

      // Remove trailing commas before closing brackets/braces
      s = s.replace(/,\s*([}\]])/g, '$1');

      return JSON.parse(s);
    } catch {
      return null;
    }
  }
};

const extractFirstJsonObject = (text) => {
  if (typeof text !== "string") {
    return null;
  }

  const start = text.indexOf("{");
  if (start === -1) {
    return null;
  }

  let depth = 0;
  for (let i = start; i < text.length; i += 1) {
    const char = text[i];
    if (char === "{") depth += 1;
    if (char === "}") depth -= 1;

    if (depth === 0) {
      const candidate = text.slice(start, i + 1);
      const parsed = tryParseJson(candidate);
      if (parsed && typeof parsed === "object") {
        return parsed;
      }
    }
  }

  return null;
};

const toArray = (value) => {
  if (Array.isArray(value)) {
    return value
      .map((item) => cleanText(String(item ?? "")))
      .filter(Boolean);
  }

  if (typeof value === "string") {
    const parsed = tryParseJson(value);
    if (Array.isArray(parsed)) {
      return parsed
        .map((item) => cleanText(String(item ?? "")))
        .filter(Boolean);
    }

    return value
      .split(/\r?\n/)
      .map((line) => line.replace(/^\s*(?:[-*]|\d+[.)])\s*/, "").trim())
      .filter(Boolean);
  }

  return [];
};

const normalizeResult = (result) => {
  if (result == null) return null;

  // Unwrap common wrappers: axios may return { data: {...} }
  let normalized;

  if (typeof result === "string") {
    // The API sometimes returns the whole JSON as a string
    const embedded = extractFirstJsonObject(result);
    normalized = embedded && typeof embedded === "object" ? { ...embedded } : { lesson: String(result) };
  } else if (typeof result === "object" && result.data && typeof result.data === "object") {
    normalized = { ...result.data };
  } else if (typeof result === "object") {
    normalized = { ...result };
  } else {
    return null;
  }

  if (typeof normalized.lesson === "string") {
    // If lesson contains a JSON string (possibly double-encoded), try parsing it directly
    const parsedLessonDirect = tryParseJson(normalized.lesson);
    if (parsedLessonDirect && typeof parsedLessonDirect === "object") {
      normalized = { ...normalized, ...parsedLessonDirect };
    } else {
      // Try unescaping common escape sequences then extract embedded object
      const unescaped = String(normalized.lesson)
        .replace(/\\n/g, "\n")
        .replace(/\\"/g, '"')
        .replace(/\\'/g, "'");

      const embeddedJson = extractFirstJsonObject(unescaped);
      if (embeddedJson && typeof embeddedJson === "object") {
        normalized = { ...normalized, ...embeddedJson };
      }
    }
  }

  // Ensure `lesson` is a readable text. If it still contains embedded JSON, try to extract the inner lesson string.
  const extractStringValueFromText = (key, text) => {
    if (!text || typeof text !== 'string') return null;

    const pos = text.indexOf(`"${key}"`);
    const startIndex = pos >= 0 ? pos : text.indexOf(key);
    if (startIndex === -1) return null;

    // find ':' after the key
    const colon = text.indexOf(':', startIndex);
    if (colon === -1) return null;

    // find the first quote after colon
    let i = colon + 1;
    while (i < text.length && /\s/.test(text[i])) i++;
    if (i >= text.length) return null;
    const quote = text[i];
    if (quote !== '"' && quote !== "'") return null;

    // parse string value handling escapes
    let j = i + 1;
    let out = '';
    while (j < text.length) {
      const ch = text[j];
      if (ch === "\\") {
        const next = text[j+1] || '';
        out += next;
        j += 2;
        continue;
      }
      if (ch === quote) {
        return out;
      }
      out += ch;
      j++;
    }
    return null;
  };

  if (typeof normalized.lesson === 'string') {
    const maybe = extractStringValueFromText('lesson', normalized.lesson);
    if (maybe) {
      normalized.lesson = maybe;
    }
  }

  const titleText = typeof normalized.title === 'string' && normalized.title.trim()
    ? normalized.title.trim()
    : (typeof normalized.topic === 'string' && normalized.topic.trim() ? normalized.topic.trim() : 'Lesson');
  const levelText = typeof normalized.level === 'string' && normalized.level.trim()
    ? normalized.level.trim()
    : 'A2-B1';

  // Normalize exercise_groups if present (backend now returns grouped exercises)
  const rawGroups = normalized.exercise_groups || normalized.exerciseGroups || normalized.groups || null;
  const exercise_groups = Array.isArray(rawGroups)
    ? rawGroups.map((g) => ({
        title: cleanText(String(g.title ?? g.group_title ?? g.groupTitle ?? "")),
        type: cleanText(String(g.type ?? "exercise")),
        instructions: cleanText(String(g.instructions ?? "")),
        questions: toArray(g.questions ?? g.exercises ?? g.items ?? g.text ?? []),
        answers: toArray(g.answers ?? []),
        solutions_arabic: toArray(g.solutions_arabic ?? g.solutionsArabic ?? g.solutions ?? []),
      }))
    : [];

  const normalizedGrammarPoints = toArray(normalized.grammar_points);
  const normalizedExamples = toArray(normalized.examples);
  const normalizedVocabulary = Array.isArray(normalized.vocabulary)
    ? normalized.vocabulary
        .map((item) => ({
          german: cleanText(String(item?.german ?? item?.word ?? item?.term ?? "")),
          arabic: cleanText(String(item?.arabic ?? item?.meaning_arabic ?? item?.translation ?? "")),
          example: cleanText(String(item?.example ?? item?.example_german ?? "")),
          pronunciation: cleanText(String(item?.pronunciation ?? item?.pronunciation_notes ?? "")),
        }))
        .filter((item) => item.german || item.arabic || item.example)
    : [];

  const learning_objectives = toArray(normalized.learning_objectives);
  const common_mistakes = Array.isArray(normalized.common_mistakes)
    ? normalized.common_mistakes.map((cm) => ({
        mistake: cleanText(String(cm.mistake ?? "")),
        correction: cleanText(String(cm.correction ?? "")),
        explanation_arabic: cleanText(String(cm.explanation_arabic ?? cm.explanation ?? "")),
      }))
    : [];
  const usage_notes = toArray(normalized.usage_notes);
  const grammar_tips = toArray(normalized.grammar_tips);
  const memory_tricks = toArray(normalized.memory_tricks);
  const summary = cleanText(String(normalized.summary ?? normalized.final_summary ?? ""));

  // Flatten exercises/solutions if top-level arrays are missing
  const topExercises = toArray(normalized.exercises);
  const topSolutions = toArray(normalized.solutions_arabic);

  const flatExercises = topExercises.length > 0 ? topExercises : exercise_groups.flatMap((g) => g.questions || []);
  const flatSolutions = topSolutions.length > 0 ? topSolutions : exercise_groups.flatMap((g) => g.solutions_arabic || []);
  const flatAnswers = Array.isArray(normalized.answers) && normalized.answers.length > 0
    ? toArray(normalized.answers)
    : exercise_groups.flatMap((g) => g.answers || []);

  // Fallback: if still empty, try extracting arrays directly from the lesson string
  const lessonText = typeof normalized.lesson === "string" ? normalized.lesson : "";

  const extractJsonArrayByKey = (key) => {
    if (!lessonText) return [];
    // find key occurrence (with or without quotes)
    let idx = lessonText.indexOf(`"${key}"`);
    if (idx === -1) idx = lessonText.indexOf(`'${key}'`);
    if (idx === -1) idx = lessonText.indexOf(key);
    if (idx === -1) return [];

    const bracketStart = lessonText.indexOf('[', idx);
    if (bracketStart === -1) return [];

    // find matching closing bracket with depth counting
    let depth = 0;
    let endIndex = -1;
    for (let i = bracketStart; i < lessonText.length; i += 1) {
      const ch = lessonText[i];
      if (ch === '[') depth += 1;
      else if (ch === ']') {
        depth -= 1;
        if (depth === 0) { endIndex = i; break; }
      }
    }

    const snippet = endIndex !== -1
      ? lessonText.slice(bracketStart, endIndex + 1)
      : `${lessonText.slice(bracketStart)}]`;

    const parsed = tryParseJson(snippet);
    if (Array.isArray(parsed)) return parsed.map((it) => cleanText(String(it ?? ""))).filter(Boolean);

    // fallback: extract quoted strings inside snippet
    const items = [];
    const qRe = /["']([^"']{1,}?)['"]/g;
    let m;
    while ((m = qRe.exec(snippet)) !== null) {
      if (m[1] && m[1].trim()) items.push(cleanText(m[1]));
    }
    // last resort: split on line breaks
    if (items.length === 0) {
      return snippet.split(/\r?\n/).map((l) => l.replace(/^[\s\],\d.*-]+/, '').trim()).filter(Boolean);
    }
    return items;
  };

  const fallbackExercises = flatExercises.length > 0 ? flatExercises : extractJsonArrayByKey("exercises");
  const fallbackSolutions = flatSolutions.length > 0 ? flatSolutions : (extractJsonArrayByKey("solutions_arabic").length ? extractJsonArrayByKey("solutions_arabic") : extractJsonArrayByKey("solutions"));
  const fallbackExercisesFinal = fallbackExercises.length ? fallbackExercises : extractJsonArrayByKey("exercises");

  const fallbackSentences = Array.isArray(normalized.sentences) && normalized.sentences.length > 0
    ? toArray(normalized.sentences)
    : extractJsonArrayByKey("sentences");

  const fallbackQuestions = Array.isArray(normalized.questions) && normalized.questions.length > 0
    ? toArray(normalized.questions)
    : extractJsonArrayByKey("questions");

  const fallbackAnswers = Array.isArray(normalized.answers) && normalized.answers.length > 0
    ? toArray(normalized.answers)
    : extractJsonArrayByKey("answers");

  // Final recovery: if top-level arrays are empty but `lesson` contains a JSON object,
  // parse that object and prefer its arrays (covers the payload shape you pasted).
  try {
    const needsRecovery = (
      (!fallbackExercises || fallbackExercises.length === 0) ||
      (!fallbackSentences || fallbackSentences.length === 0) ||
      (!fallbackSolutions || fallbackSolutions.length === 0) ||
      (!fallbackQuestions || fallbackQuestions.length === 0) ||
      (!fallbackAnswers || fallbackAnswers.length === 0)
    );

    if (needsRecovery && typeof normalized.lesson === 'string') {
      // try direct parse first
      let inner = tryParseJson(normalized.lesson);
      if (!inner) {
        const unescaped = String(normalized.lesson).replace(/\\n/g, "\n").replace(/\\"/g, '"').replace(/\\'/g, "'");
        inner = extractFirstJsonObject(unescaped) || tryParseJson(unescaped);
      }

      if (inner && typeof inner === 'object') {
        // prefer inner arrays when available
        const innerExercises = Array.isArray(inner.exercises) ? inner.exercises.map((it) => cleanText(String(it ?? ""))).filter(Boolean) : [];
        const innerSentences = Array.isArray(inner.sentences) ? inner.sentences.map((it) => cleanText(String(it ?? ""))).filter(Boolean) : [];
        const innerSolutions = Array.isArray(inner.solutions_arabic) ? inner.solutions_arabic.map((it) => cleanText(String(it ?? ""))).filter(Boolean) : (Array.isArray(inner.solutions) ? inner.solutions.map((it) => cleanText(String(it ?? ""))).filter(Boolean) : []);
        const innerQuestions = Array.isArray(inner.questions) ? inner.questions.map((it) => cleanText(String(it ?? ""))).filter(Boolean) : [];
        const innerAnswers = Array.isArray(inner.answers) ? inner.answers.map((it) => cleanText(String(it ?? ""))).filter(Boolean) : [];

        if ((!fallbackExercises || fallbackExercises.length === 0) && innerExercises.length > 0) fallbackExercises.splice(0, fallbackExercises.length, ...innerExercises);
        if ((!fallbackSentences || fallbackSentences.length === 0) && innerSentences.length > 0) fallbackSentences.splice(0, fallbackSentences.length, ...innerSentences);
        if ((!fallbackSolutions || fallbackSolutions.length === 0) && innerSolutions.length > 0) fallbackSolutions.splice(0, fallbackSolutions.length, ...innerSolutions);
        if ((!fallbackQuestions || fallbackQuestions.length === 0) && innerQuestions.length > 0) fallbackQuestions.splice(0, fallbackQuestions.length, ...innerQuestions);
        if ((!fallbackAnswers || fallbackAnswers.length === 0) && innerAnswers.length > 0) fallbackAnswers.splice(0, fallbackAnswers.length, ...innerAnswers);
        // if the lesson text itself is wrapped, prefer inner.lesson
        if (typeof inner.lesson === 'string' && inner.lesson.trim()) normalized.lesson = inner.lesson;
      }
    }
  } catch {
    // non-fatal: continue with whatever we have
  }

  return {
    title: titleText,
    level: levelText,
    topic: cleanText(String(normalized.topic ?? "")),
    lesson: cleanText(String(normalized.lesson ?? "")),
    grammar_points: normalizedGrammarPoints,
    vocabulary: normalizedVocabulary,
    examples: normalizedExamples.length > 0 ? normalizedExamples : fallbackSentences,
    sentences: fallbackSentences,
    exercises: (fallbackExercises.length ? fallbackExercises : fallbackExercisesFinal),
    solutions_arabic: fallbackSolutions,
    questions: fallbackQuestions,
    answers: flatAnswers.length > 0 ? flatAnswers : fallbackAnswers,
    exercise_groups,
    learning_objectives,
    common_mistakes,
    usage_notes,
    grammar_tips,
    memory_tricks,
    summary,
  };
};

// Attempt to unwrap API response early so UI receives top-level arrays when possible
const unwrapApiResponse = (data) => {
  if (!data || typeof data !== 'object') return data;
  const out = { ...data };

  try {
    if (typeof out.lesson === 'string') {
      // Try direct parse
      let parsed = tryParseJson(out.lesson);

      // Try unescaping and extracting embedded object
      if (!parsed) {
        const unescaped = String(out.lesson).replace(/\\n/g, "\n").replace(/\\"/g, '"').replace(/\\'/g, "'");
        parsed = extractFirstJsonObject(unescaped) || tryParseJson(unescaped);
      }

      if (parsed && typeof parsed === 'object') {
        // Merge parsed into out, prefer parsed values for arrays
        const merged = { ...out, ...parsed };
        // ensure lesson text is a string (prefer parsed.lesson if present)
        merged.lesson = typeof parsed.lesson === 'string' ? parsed.lesson : out.lesson;
        return merged;
      }
    }
  } catch {
    // swallow
  }

  return out;
};

const pairExercisesAndSolutions = (exercises, solutions) => {
  const max = Math.max(exercises.length, solutions.length);
  return Array.from({ length: max }, (_, index) => ({
    exercise: exercises[index] ?? "",
    solution: solutions[index] ?? "",
    index: index + 1,
  })).filter((row) => row.exercise || row.solution);
};

const buildLocalFallbackLesson = (topic) => {
  const topicLabel = topic?.trim() || "German grammar";

  const grammarPoints = [
    "Achte auf die Funktion der Endung im Satz.",
    "Prüfe den Artikel vor der Adjektivendung.",
    "Vergleiche Kasus, Genus und Numerus.",
  ];

  const vocabulary = [
    { german: "das Adjektiv", arabic: "الصفة", example: "Das Adjektiv beschreibt ein Nomen." },
    { german: "die Endung", arabic: "النهاية الصرفية", example: "Die Endung zeigt die grammatische Form." },
    { german: "der Artikel", arabic: "أداة التعريف", example: "Der Artikel hilft bei der Deklination." },
  ];

  const examples = [
    `Ich übe heute das Thema ${topicLabel}.`,
    "Die Lehrerin erklärt die Regel langsam und deutlich.",
    "Wir wiederholen die Beispiele im Kurs gemeinsam.",
  ];

  const exerciseGroups = [
    {
      title: "Fill in the blanks",
      type: "fill_blank",
      instructions: "Ergänze die passende Form und begründe sie auf Arabisch.",
      questions: ["Ich ____ Ahmed.", "Das ist ____ gute Idee."],
      answers: ["bin", "eine"],
      solutions_arabic: ["لأن الضمير Ich يحتاج bin.", "لأن Idee مؤنث وتحتاج eine."],
    },
    {
      title: "Multiple Choice",
      type: "multiple_choice",
      instructions: "Wähle die richtige Antwort.",
      questions: ["Wir brauchen ____ Lösung."],
      answers: ["eine"],
      solutions_arabic: ["لأن Lösung مؤنث، لذا نستخدم eine."],
    },
  ];

  return {
    title: topicLabel,
    level: "A2-B1",
    topic: topicLabel,
    lesson: [
      `Thema: ${topicLabel}`,
      "شرح عربي: هذا كتاب عمل محلي احتياطي يظهر عند تعذر الاتصال بالخادم، لكنه يبقى منسقًا ويحتوي على تمارين وحلول عربية.",
      "German note: Use this as a clean draft until the live API becomes available again.",
      "Exam Tip: راجع القاعدة مع أمثلة قصيرة قبل الانتقال إلى التمارين الأطول.",
    ].join("\n\n"),
    grammar_points: grammarPoints,
    vocabulary,
    examples,
    sentences: examples,
    exercises: exerciseGroups.flatMap((group) => group.questions),
    solutions_arabic: exerciseGroups.flatMap((group) => group.solutions_arabic),
    questions: [
      "Was ist das Thema dieser Lektion?",
      "Warum ist die Regel nützlich?",
      "Wie kann man die Regel gut üben?",
    ],
    answers: [
      topicLabel,
      "Sie hilft beim Sprechen, Schreiben und in Prüfungen.",
      "Mit kurzen Beispielen, Wiederholung und Korrektur auf Arabisch.",
    ],
    exercise_groups: exerciseGroups,
  };
};

export default function Input() {
  const [lesson, setLesson] = useState("");
  const [result, setResult] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const handleForm = async (event) => {
    event.preventDefault();

    const trimmedLesson = lesson.trim();

    if (!trimmedLesson) {
      setError("Please enter a topic before generating.");
      setMessage("");
      return;
    }

    setIsSubmitting(true);
    setError("");
    setMessage("");

    try {
      const response = await axios.post("http://127.0.0.1:8000/api/generate", {
        topic: trimmedLesson,
      });
      const unwrapped = unwrapApiResponse(response.data ?? null);
      const normalizedResult = normalizeResult(unwrapped ?? null);
      setResult(normalizedResult ? { ...normalizedResult, __normalized: true } : (unwrapped ?? null));
      console.log('raw response:', response.data);
      setMessage("Lesson generated successfully.");
    } catch {
      const fallbackLesson = buildLocalFallbackLesson(trimmedLesson);
      setResult({ ...fallbackLesson, __normalized: true });
      setMessage("Backend unavailable, so an offline draft was created instead.");
      setError("");
    } finally {
      setIsSubmitting(false);
    }
  };

  const displayResult = useMemo(() => {
    if (!result) return null;
    if (result.__normalized) return normalizeResult(result) || result;
    return normalizeResult(result);
  }, [result]);

  // For UI we show the complete set of exercises.
  const displayExercises = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.exercises)
      ? displayResult.exercises
      : [];
  }, [displayResult]);

  const displaySentences = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.sentences) ? displayResult.sentences : [];
  }, [displayResult]);

  const displayQuestions = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.questions) ? displayResult.questions : [];
  }, [displayResult]);

  const displayAnswers = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.answers) ? displayResult.answers : [];
  }, [displayResult]);

  const displaySolutions = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.solutions_arabic)
      ? displayResult.solutions_arabic
      : [];
  }, [displayResult]);

  const displayGrammarPoints = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.grammar_points) ? displayResult.grammar_points : [];
  }, [displayResult]);

  const displayVocabulary = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.vocabulary) ? displayResult.vocabulary : [];
  }, [displayResult]);

  const displayExamples = useMemo(() => {
    if (!displayResult) return [];
    if (Array.isArray(displayResult.examples) && displayResult.examples.length > 0) {
      return displayResult.examples;
    }

    return Array.isArray(displayResult.sentences) ? displayResult.sentences : [];
  }, [displayResult]);

  const displayExerciseGroups = useMemo(() => {
    if (!displayResult) return [];
    return Array.isArray(displayResult.exercise_groups) ? displayResult.exercise_groups : [];
  }, [displayResult]);

  const exerciseSolutionPairs = useMemo(() => {
    return pairExercisesAndSolutions(displayExercises, displaySolutions);
  }, [displayExercises, displaySolutions]);

  const renderList = (items, emptyMessage, className) => {
    if (!Array.isArray(items) || items.length === 0) {
      return <p className="muted">{emptyMessage}</p>;
    }

    return (
      <ul className={className}>
        {items.map((item, index) => (
          <li key={`${item}-${index}`}>{item}</li>
        ))}
      </ul>
    );
  };

  const renderLessonParagraphs = (text) => {
    if (!text) return <p className="muted">No lesson returned.</p>;
    const parts = String(text)
      .split(/\n\n+/)
      .map((p) => p.trim())
      .filter(Boolean);

    return parts.map((p, i) => (
      <p key={`para-${i}`} className="result-copy__para">{p}</p>
    ));
  };

  const appendValue = (form, name, value) => {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value == null ? "" : String(value);
    form.appendChild(input);
  };

  const appendArray = (form, name, values) => {
    if (!Array.isArray(values)) return;

    values.forEach((value, index) => {
      if (Array.isArray(value)) {
        appendArray(form, `${name}[${index}]`, value);
        return;
      }

      if (value && typeof value === "object") {
        Object.entries(value).forEach(([key, nestedValue]) => {
          if (Array.isArray(nestedValue)) {
            appendArray(form, `${name}[${index}][${key}]`, nestedValue);
          } else if (nestedValue && typeof nestedValue === "object") {
            Object.entries(nestedValue).forEach(([childKey, childValue]) => {
              appendValue(form, `${name}[${index}][${key}][${childKey}]`, childValue);
            });
          } else {
            appendValue(form, `${name}[${index}][${key}]`, nestedValue);
          }
        });
        return;
      }

      appendValue(form, `${name}[${index}]`, value);
    });
  };

  const handleExportPDF = async () => {
    const topic = displayResult?.topic
      ? displayResult.topic.replace(/\s+/g, "_")
      : "lesson";
    try {
      const payload = displayResult ? { ...displayResult } : {};
      delete payload.__normalized;

      const form = document.createElement("form");
      form.method = "POST";
      form.action = "http://127.0.0.1:8000/api/export-pdf";
      form.target = "_blank";
      form.style.display = "none";

      appendValue(form, "topic", payload.topic ?? topic);
      appendValue(form, "lesson", payload.lesson ?? "");
      appendValue(form, "title", payload.title ?? payload.topic ?? topic);
      appendValue(form, "level", payload.level ?? "A2-B1");
      appendArray(form, "grammar_points", payload.grammar_points ?? []);
      appendArray(form, "vocabulary", payload.vocabulary ?? []);
      appendArray(form, "examples", payload.examples ?? []);
      appendArray(form, "sentences", payload.sentences ?? []);
      appendArray(form, "questions", payload.questions ?? []);
      appendArray(form, "answers", payload.answers ?? []);
      appendArray(form, "exercise_groups", payload.exercise_groups ?? []);
      appendArray(form, "learning_objectives", payload.learning_objectives ?? []);
      appendArray(form, "common_mistakes", payload.common_mistakes ?? []);
      appendArray(form, "usage_notes", payload.usage_notes ?? []);
      appendArray(form, "grammar_tips", payload.grammar_tips ?? []);
      appendArray(form, "memory_tricks", payload.memory_tricks ?? []);
      appendValue(form, "summary", payload.summary ?? "");

      document.body.appendChild(form);
      form.submit();
      form.remove();
      setError("");
      setMessage("PDF is being generated by the backend DomPDF renderer.");
    } catch (error) {
      console.error('Export PDF failed', error);
      setError('Unable to export PDF from the backend right now. Please try again.');
    }
  };

    return (
      <main className="input-page">
        <section className="input-shell">
          <section className="input-hero" aria-labelledby="lesson-generator-title">
            <p className="input-card__eyebrow">Lesson generator</p>
            <h1 id="lesson-generator-title">Create polished lessons in seconds</h1>
            <p className="input-card__subtitle">
              Turn a topic into a structured teaching draft with exercises, questions, answers, and Arabic support notes.
            </p>

            <div className="hero-highlights" aria-label="Key benefits">
              <div className="hero-highlight">
                <span>Fast</span>
                <strong>Prompt to draft</strong>
              </div>
              <div className="hero-highlight">
                <span>Structured</span>
                <strong>Lesson, exercises, review</strong>
              </div>
              <div className="hero-highlight">
                <span>Exportable</span>
                <strong>PDF-ready output</strong>
              </div>
            </div>
          </section>

          <section className="input-card input-card--form" aria-labelledby="lesson-form-title">
            <div className="input-card__header">
              <p className="input-card__eyebrow">Start here</p>
              <h2 id="lesson-form-title">Describe the lesson topic</h2>
              <p className="input-card__subtitle">
                Keep it specific. The assistant works best with short topics such as verb tenses, grammar rules, or vocabulary themes.
              </p>
            </div>

            <form className="input-form" onSubmit={handleForm}>
              <div className="input-form__field">
                <label htmlFor="lesson-topic">Topic</label>

                <input
                  id="lesson-topic"
                  type="text"
                  name="lesson"
                  value={lesson}
                  onChange={(event) => setLesson(event.target.value)}
                  placeholder="Example: Perfect tense"
                  autoComplete="off"
                  maxLength={120}
                />

                <div className="input-form__meta">
                  <span>Keep it short and specific.</span>
                  <span>{lesson.length}/120</span>
                </div>
              </div>

              {error && <p className="feedback feedback--error">{error}</p>}
              {message && <p className="feedback feedback--success">{message}</p>}

              <div className="input-form__actions">
                <button
                  type="submit"
                  className="input-form__button"
                  disabled={isSubmitting}
                >
                  {isSubmitting ? "Generating..." : "Generate lesson"}
                </button>
                <p className="input-form__hint">
                  The generated lesson will appear below with an export option.
                </p>
              </div>
            </form>
          </section>

          <section className="results-panel" aria-live="polite">
            {displayResult ? (
              <div className="input-results__card">
                <header className="result-header">
                  <div>
                    <p className="input-card__eyebrow">Generated output</p>
                    <h2 className="result-title">{displayResult.title || displayResult.topic || "Untitled lesson"}</h2>
                    <p className="result-subtitle">{displayResult.level ? `${displayResult.level} • ` : ""}An academic workbook draft organized for teaching and quick review.</p>
                  </div>

                  <button type="button" className="export-button" onClick={handleExportPDF}>
                    Export PDF
                  </button>
                </header>

                <div className="result-metrics">
                  <div className="metric-card">
                    <span>Groups</span>
                    <strong>{displayExerciseGroups.length}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Exercises</span>
                    <strong>{displayExercises.length}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Vocabulary</span>
                    <strong>{displayVocabulary.length}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Grammar points</span>
                    <strong>{displayGrammarPoints.length}</strong>
                  </div>
                </div>

                <div className="result-grid">
                  <article className="result-card result-card--wide">
                    <h3>Lesson overview</h3>
                    {renderLessonParagraphs(displayResult.lesson)}
                  </article>

                  <article className="result-card">
                    <h3>Grammar summary</h3>
                    {renderList(displayGrammarPoints, "No grammar points returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Vocabulary</h3>
                    {displayVocabulary.length > 0 ? (
                      <table className="result-table">
                        <thead>
                          <tr>
                            <th>German</th>
                            <th>Arabic</th>
                            <th>Example</th>
                          </tr>
                        </thead>
                        <tbody>
                          {displayVocabulary.map((item, index) => (
                            <tr key={`voc-${index}`}>
                              <td>{item.german || ""}</td>
                              <td dir="rtl">{item.arabic || ""}</td>
                              <td>{item.example || ""}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    ) : (
                      <p className="muted">No vocabulary returned.</p>
                    )}
                  </article>

                  <article className="result-card result-card--wide">
                    <h3>Examples</h3>
                    {renderList(displayExamples, "No examples returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Exercises</h3>
                    {displayExercises.length > 0 ? (
                      <ol className="result-list result-list--numbered">
                        {displayExercises.map((item, index) => (
                          <li key={`ex-${index}`}>{item}</li>
                        ))}
                      </ol>
                    ) : (
                      <p className="muted">No exercises returned.</p>
                    )}
                  </article>

                  <article className="result-card">
                    <h3>Sentences</h3>
                    {renderList(displaySentences, "No sentences returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Questions</h3>
                    {renderList(displayQuestions, "No questions returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Answers</h3>
                    {displayAnswers.length > 0 ? (
                      <ol className="result-list result-list--numbered">
                        {displayAnswers.map((answer, index) => (
                          <li key={`${answer}-${index}`}>{answer}</li>
                        ))}
                      </ol>
                    ) : (
                      <p className="muted">No answers returned.</p>
                    )}
                  </article>

                  <article className="result-card result-card--wide">
                    <h3>Exercise corrections</h3>
                    {displayExerciseGroups.length > 0 ? (
                      displayExerciseGroups.map((group, groupIndex) => {
                        const questions = Array.isArray(group.questions) ? group.questions : [];
                        const answers = Array.isArray(group.answers) ? group.answers : [];
                        const solutions = Array.isArray(group.solutions_arabic) ? group.solutions_arabic : [];
                        const count = Math.max(questions.length, answers.length, solutions.length);

                        return (
                          <div className="exercise-solution-grid" key={`group-${groupIndex}`}>
                            <div className="exercise-solution-item">
                              <p className="pair-title">{group.title || group.group_title || `Group ${groupIndex + 1}`}</p>
                              <p className="pair-title pair-title--arabic">{group.type || "exercise"}</p>
                              {group.instructions ? <p className="muted">{group.instructions}</p> : null}
                              <table className="result-table">
                                <thead>
                                  <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Answer</th>
                                    <th>Arabic explanation</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  {Array.from({ length: count }, (_, index) => (
                                    <tr key={`group-${groupIndex}-row-${index}`}>
                                      <td>{index + 1}</td>
                                      <td>{questions[index] || ""}</td>
                                      <td>{answers[index] || ""}</td>
                                      <td dir="rtl">{solutions[index] || ""}</td>
                                    </tr>
                                  ))}
                                </tbody>
                              </table>
                            </div>
                          </div>
                        );
                      })
                    ) : exerciseSolutionPairs.length > 0 ? (
                      <div className="exercise-solution-grid">
                        {exerciseSolutionPairs.map((pair) => (
                          <div className="exercise-solution-item" key={`pair-${pair.index}`}>
                            <p className="pair-title">{pair.index}. {pair.exercise || "No exercise text."}</p>
                            <p className="pair-title pair-title--arabic">Correction and Arabic explanation</p>
                            <p className="pair-solution" dir="rtl">{pair.solution || "لا يوجد شرح."}</p>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="muted">No corrections returned.</p>
                    )}
                  </article>
                </div>
              </div>
            ) : (
              <div className="input-results__card input-results__card--empty">
                <p className="input-card__eyebrow">Preview</p>
                <h2>Your generated lesson will appear here</h2>
                <p className="result-subtitle">
                  Submit a topic above and this area will fill with the lesson, examples, exercises, and review questions.
                </p>
              </div>
            )}
          </section>
        </section>
      </main>
    );
}