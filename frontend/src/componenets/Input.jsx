import { useMemo, useRef, useState } from "react";
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
    return null;
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
  if (!result || typeof result !== "object") {
    return null;
  }

  let normalized = { ...result };

  if (typeof normalized.lesson === "string") {
    const embeddedJson = extractFirstJsonObject(normalized.lesson);
    if (embeddedJson && typeof embeddedJson === "object") {
      normalized = { ...normalized, ...embeddedJson };
    }
  }

  return {
    topic: cleanText(String(normalized.topic ?? "")),
    lesson: cleanText(String(normalized.lesson ?? "")),
    sentences: toArray(normalized.sentences),
    exercises: toArray(normalized.exercises),
    solutions_arabic: toArray(normalized.solutions_arabic),
    questions: toArray(normalized.questions),
    answers: toArray(normalized.answers),
  };
};

const pairExercisesAndSolutions = (exercises, solutions) => {
  const max = Math.max(exercises.length, solutions.length);
  return Array.from({ length: max }, (_, index) => ({
    exercise: exercises[index] ?? "",
    solution: solutions[index] ?? "",
    index: index + 1,
  })).filter((row) => row.exercise || row.solution);
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
      setResult(response.data ?? null);
      console.log(response.data);
      setMessage("Lesson generated successfully.");
    } catch (requestError) {
      setError(
        requestError?.response?.data?.message ||
        "Unable to generate the lesson right now. Please try again."
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const resultsRef = useRef(null);
  const displayResult = useMemo(() => normalizeResult(result), [result]);

  const exerciseSolutionPairs = useMemo(() => {
    if (!displayResult) {
      return [];
    }

    return pairExercisesAndSolutions(
      displayResult.exercises,
      displayResult.solutions_arabic
    );
  }, [displayResult]);

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

  const renderOrdered = (items, emptyMessage, className) => {
    if (!Array.isArray(items) || items.length === 0) {
      return <p className="muted">{emptyMessage}</p>;
    }

    return (
      <ol className={className}>
        {items.map((item, index) => (
          <li key={`${item}-${index}`}>{item}</li>
        ))}
      </ol>
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

  const renderQAPairs = (questions, answers) => {
    const max = Math.max(questions.length, answers.length);
    if (max === 0) return <p className="muted">No questions/answers returned.</p>;

    const rows = Array.from({ length: max }, (_, i) => ({
      q: questions[i] ?? "",
      a: answers[i] ?? "",
      i: i + 1,
    }));

    return (
      <div className="qa-grid">
        {rows.map((r) => (
          <div className="qa-row" key={`qa-${r.i}`}>
            <div className="qa-question"><strong>{r.i}.</strong> {r.q}</div>
            <div className="qa-answer">{r.a}</div>
          </div>
        ))}
      </div>
    );
  };

  const handleExportPDF = async () => {
    if (!resultsRef.current) return;
    const topic = displayResult?.topic
      ? displayResult.topic.replace(/\s+/g, "_")
      : "lesson";
    try {
      // dynamic imports so build doesn't fail if packages aren't installed yet
      const html2canvas = (await import('html2canvas')).default;
      const { jsPDF } = await import('jspdf');

      const node = resultsRef.current;
      const canvas = await html2canvas(node, { scale: 2 });
      const imgData = canvas.toDataURL('image/png');

      const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
      const pdfWidth = pdf.internal.pageSize.getWidth();
      const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

      pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
      pdf.save(`${topic}.pdf`);
    } catch (err) {
      console.error('Export PDF failed', err);
      setError('Unable to export PDF. Make sure `html2canvas` and `jspdf` are installed.');
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
              <div className="input-results__card" ref={resultsRef}>
                <header className="result-header">
                  <div>
                    <p className="input-card__eyebrow">Generated output</p>
                    <h2 className="result-title">{displayResult.topic || "Untitled lesson"}</h2>
                    <p className="result-subtitle">A clean lesson draft organized for teaching and quick review.</p>
                  </div>

                  <button type="button" className="export-button" onClick={handleExportPDF}>
                    Export PDF
                  </button>
                </header>

                <div className="result-metrics">
                  <div className="metric-card">
                    <span>Exercises</span>
                    <strong>{displayResult.exercises.length}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Sentences</span>
                    <strong>{displayResult.sentences.length}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Questions</span>
                    <strong>{displayResult.questions.length}</strong>
                  </div>
                </div>

                <div className="result-grid">
                  <article className="result-card result-card--wide">
                    <h3>Lesson overview</h3>
                    <p className="result-copy">{displayResult.lesson || "No lesson returned."}</p>
                  </article>

                  <article className="result-card">
                    <h3>Exercises</h3>
                    {renderList(displayResult.exercises, "No exercises returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Sentences</h3>
                    {renderList(displayResult.sentences, "No sentences returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Questions</h3>
                    {renderList(displayResult.questions, "No questions returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Answers</h3>
                    {displayResult.answers.length > 0 ? (
                      <ol className="result-list result-list--numbered">
                        {displayResult.answers.map((answer, index) => (
                          <li key={`${answer}-${index}`}>{answer}</li>
                        ))}
                      </ol>
                    ) : (
                      <p className="muted">No answers returned.</p>
                    )}
                  </article>

                  <article className="result-card result-card--wide">
                    <h3>Exercise + Arabic support</h3>
                    {exerciseSolutionPairs.length > 0 ? (
                      <div className="exercise-solution-grid">
                        {exerciseSolutionPairs.map((pair) => (
                          <div className="exercise-solution-item" key={`pair-${pair.index}`}>
                            <p className="pair-title">Exercise {pair.index}</p>
                            <p className="pair-exercise">{pair.exercise || "No exercise text."}</p>
                            <p className="pair-title pair-title--arabic">Arabic support</p>
                            <p className="pair-solution" dir="rtl">{pair.solution || "لا يوجد شرح."}</p>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="muted">No Arabic support returned.</p>
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