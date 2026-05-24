import { useState, useRef } from "react";
import axios from "axios";
import "./Input.css";

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

  const handleExportPDF = async () => {
    if (!resultsRef.current) return;
    const topic = (result && result.topic) ? result.topic.replace(/\s+/g, '_') : 'lesson';
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
            {result ? (
              <div className="input-results__card" ref={resultsRef}>
                <header className="result-header">
                  <div>
                    <p className="input-card__eyebrow">Generated output</p>
                    <h2 className="result-title">{result.topic}</h2>
                    <p className="result-subtitle">A clean lesson draft organized for teaching and quick review.</p>
                  </div>

                  <button type="button" className="export-button" onClick={handleExportPDF}>
                    Export PDF
                  </button>
                </header>

                <div className="result-metrics">
                  <div className="metric-card">
                    <span>Exercises</span>
                    <strong>{Array.isArray(result.exercises) ? result.exercises.length : 0}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Sentences</span>
                    <strong>{Array.isArray(result.sentences) ? result.sentences.length : 0}</strong>
                  </div>
                  <div className="metric-card">
                    <span>Questions</span>
                    <strong>{Array.isArray(result.questions) ? result.questions.length : 0}</strong>
                  </div>
                </div>

                <div className="result-grid">
                  <article className="result-card result-card--wide">
                    <h3>Lesson overview</h3>
                    <p className="result-copy">{result.lesson || "No lesson returned."}</p>
                  </article>

                  <article className="result-card">
                    <h3>Arabic support</h3>
                    <div className="solution-box">
                      {Array.isArray(result.solutions_arabic)
                        ? result.solutions_arabic.map((solution, index) => (
                            <p key={`${solution}-${index}`}>{solution}</p>
                          ))
                        : (result.solutions_arabic || "No solution returned.")}
                    </div>
                  </article>

                  <article className="result-card">
                    <h3>Exercises</h3>
                    {renderList(result.exercises, "No exercises returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Sentences</h3>
                    {renderList(result.sentences, "No sentences returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Questions</h3>
                    {renderList(result.questions, "No questions returned.", "result-list")}
                  </article>

                  <article className="result-card">
                    <h3>Answers</h3>
                    {Array.isArray(result.answers) ? (
                      <ol className="result-list result-list--numbered">
                        {result.answers.map((answer, index) => (
                          <li key={`${answer}-${index}`}>{answer}</li>
                        ))}
                      </ol>
                    ) : (
                      <p className="muted">{result.answers || "No answers returned."}</p>
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