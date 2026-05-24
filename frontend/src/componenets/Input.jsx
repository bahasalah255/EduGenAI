import { useState, useRef } from "react";
import axios from "axios";
import "./Input.css";

export default function Input() {
  const [lesson, setLesson] = useState("");
  const [result, setResult] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [form, setForm] = useState(false);

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
    // hide the form immediately when the user submits
    setForm(true);

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
        
      <section className="input-card" aria-labelledby="lesson-generator-title">
        
        <div className="input-card__header">
         {!form ? (
          <>
            <p className="input-card__eyebrow">Lesson generator</p>

            <h1 id="lesson-generator-title">Create a lesson in seconds</h1>

            <p className="input-card__subtitle">
              Enter a topic and generate a clean lesson draft with exercises and supporting content.
            </p>

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

              <button
                type="submit"
                className="input-form__button"
                disabled={isSubmitting}
              >
                {isSubmitting ? "Generating..." : "Generate lesson"}
              </button>
            </form>
          </>
        ) : null}
    </div>
  <section className="input-results" aria-live="polite">
        {result ? (
          <div className="input-results__card" ref={resultsRef}>
            <header className="result-header">
              <h2 className="result-title">Leçon: {result.topic}</h2>
              <p className="result-subtitle">Generated lesson — organized for teaching and quick review.</p>
              <div>
                <button type="button" className="export-button" onClick={handleExportPDF}>Export PDF</button>
              </div>
            </header>

            <div className="result-body">
              <section className="result-main">
                <div className="result-section result-exercises">
                  <h3>Exercises</h3>
                  {Array.isArray(result.exercises) && result.exercises.length > 0 ? (
                    <ol className="exercises-list">
                      {result.exercises.map((exercise, index) => (
                        <li key={`${exercise}-${index}`}>{exercise}</li>
                      ))}
                    </ol>
                  ) : (
                    <p className="muted">No exercises returned.</p>
                  )}
                </div>

                {Array.isArray(result.sentences) && result.sentences.length > 0 ? (
                  <div className="result-section result-sentences">
                    <h3>Sentences</h3>
                    <ul className="sentences-list">
                      {result.sentences.map((sentence, index) => (
                        <li key={`${sentence}-${index}`}>{sentence}</li>
                      ))}
                    </ul>
                  </div>
                ) : null}

                {Array.isArray(result.questions) && result.questions.length > 0 && (
                  <div className="result-section result-questions">
                    <h3>Questions</h3>
                    <ul className="questions-list">
                      {result.questions.map((question, index) => (
                        <li key={`${question}-${index}`}>{question}</li>
                      ))}
                    </ul>
                  </div>
                )}

                {result.answers ? (
                  <div className="result-section result-answers">
                    <h3>Answers</h3>
                    {Array.isArray(result.answers) ? (
                      <ul className="answers-list">
                        {result.answers.map((answer, index) => (
                          <li key={`${answer}-${index}`}>{answer}</li>
                        ))}
                      </ul>
                    ) : (
                      <p>{String(result.answers)}</p>
                    )}
                  </div>
                ) : null}
              </section>

              <aside className="result-aside">
                <div className="result-section result-solution">
                  <h3>Solution</h3>
                  <div className="solution-box">{result.arabic_solution || "No solution returned."}</div>
                </div>
              </aside>
            </div>

            {Array.isArray(result.sentences) && result.sentences.length > 0 ? (
              <div>
                <h3>Sentences</h3>
                {result.sentences.map((sentence, index) => (
                  <p key={`${sentence}-${index}`}>{sentence}</p>
                ))}
              </div>
            ) : null}
            {Array.isArray(result.questions) && result.questions.length > 0 && (
              <div>
                <h3>Questions</h3>
                {result.questions.map((question, index) => (
                  <p key={`${question}-${index}`}>{question}</p>
                ))}
              </div>
            )}
            {result.answers ? (
              <div>
                <h3>Answers</h3>
                {Array.isArray(result.answers) ? (
                  result.answers.map((answer, index) => (
                    <p key={`${answer}-${index}`}>{answer}</p>
                  ))
                ) : (
                  <p>{String(result.answers)}</p>
                )}
              </div>
            ) : null}
          </div>
        ) : null}

      </section>
    </section>
    </main>
  );
}