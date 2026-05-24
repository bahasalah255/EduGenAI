# EduGenAI — Developer README

This repository contains two main parts:

- `backend/` — Laravel API that forwards prompts to an AI service and returns structured lessons.
- `frontend/` — Vite + React UI where users enter a topic and receive a generated lesson.

This README provides a clear local setup guide, useful commands, API examples, and troubleshooting tips.

## Quick overview

- Backend endpoint: POST `http://127.0.0.1:8000/api/generate`
- Frontend dev server: runs via Vite (default: http://localhost:5173)

## Prerequisites

- PHP 8.1 or newer with common extensions
- Composer
- Node.js 18+ and npm
- Optional: NVIDIA API key (set `NVIDIA_API_KEY` in backend `.env`) if you plan to call the upstream AI provider

On Ubuntu/Debian install PHP SQLite support (if using SQLite) with:

```bash
sudo apt-get update
sudo apt-get install php php-xml php-mbstring php-curl php-sqlite3
```

## Backend — setup & run

1. Install dependencies and prepare environment

```bash
cd backend
composer install
cp .env.example .env
# Configure .env: set APP_KEY, APP_URL, and NVIDIA_API_KEY if you use the AI provider
php artisan key:generate
```

2. (Optional) Use SQLite for quick local development

```bash
touch database/database.sqlite
# Ensure your .env sets DB_CONNECTION=sqlite and DB_DATABASE=database/database.sqlite
```

3. Run migrations (if you add them) and serve the app

```bash
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

4. Logs

- Laravel logs: `backend/storage/logs/laravel.log`

## Frontend — setup & run

1. Install and run

```bash
cd frontend
npm install
npm run dev
```

2. Build for production

```bash
npm run build
npm run preview
```

## Running both (local dev)

Open two terminals (or use tmux):

Terminal 1 — start backend

```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2 — start frontend

```bash
cd frontend
npm run dev
```

The frontend will call the backend at `http://127.0.0.1:8000/api/generate` by default.

## API usage example

Request (JSON):

```bash
curl -X POST http://127.0.0.1:8000/api/generate \
  -H "Content-Type: application/json" \
  -d '{"topic":"Present Tense of Regular Verbs"}'
```

Response (example):

```json
{
  "topic": "Present Tense of Regular Verbs",
  "lesson": "German explanation...",
  "sentences": ["Ich esse jeden Morgen ein Stück Brot."],
  "exercises": ["Fill the blanks..."],
  "solutions_arabic": ["الجواب: ..."],
  "questions": ["Wie konjugiert man ...?"],
  "answers": ["Antwort ..."]
}
```

Note: The real response depends on the AI provider and the prompt configuration in `backend/app/Services/AIService.php`.

## Troubleshooting — common issues

- "could not find driver": PHP's PDO driver for your configured DB (sqlite/mysql) is missing. Install `php-sqlite3` or `php-mysql` as appropriate and restart PHP/CLI.
- "Target class [HandleCors] does not exist": Bootstrap middleware import missing. Ensure `bootstrap/app.php` includes `use Illuminate\\Http\\Middleware\\HandleCors;` (this repo patch fixed that earlier).
- Upstream AI timeout (client shows `cURL error 28`): network or provider problem. The backend now applies a longer timeout and a retry policy, but if the provider is unreachable you will receive a 503 JSON response with a friendly message. Check `NVIDIA_API_KEY`, provider endpoint, and connectivity.
- 500 Internal Server Error from `api/generate`: check `backend/storage/logs/laravel.log` for stack traces; the controller now reports and returns a clear message when the AI call fails.

## Useful developer commands

- Lint PHP file syntax:

```bash
php -l backend/app/Services/AIService.php
php -l backend/app/Http/Controllers/LessonController.php
```

- List routes:

```bash
cd backend
php artisan route:list --path=generate
```

- Build frontend:

```bash
cd frontend
npm run build
```

## Where to look in the code

- `backend/routes/api.php` — API route definitions
- `backend/app/Http/Controllers/LessonController.php` — endpoint wiring and error handling
- `backend/app/Services/AIService.php` — HTTP wrapper to the AI provider (timeouts, retries, prompt)
- `frontend/src/componenets/Input.jsx` — main input UI and result rendering
- `frontend/src/componenets/Input.css` — styles for the input page

## Next steps (optional)

- Add a small script (or Postman collection) to exercise the API automatically.
- Add automated tests that mock the AI provider and validate JSON parsing.
- Add a CI workflow to run `php -l` and `npm run build` on pull requests.

If you want, I can add a runnable `scripts/test-api.sh` that posts a sample topic and displays the result. Tell me if you'd like that and I'll add it.

<div align="center">

# EduGenAI

**AI-Powered German Lesson Generator**

Generate complete, structured German language lessons in seconds using state-of-the-art AI models from NVIDIA NIM.

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![NVIDIA NIM](https://img.shields.io/badge/NVIDIA%20NIM-76B900?style=flat-square&logo=nvidia&logoColor=white)](https://build.nvidia.com/models)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

[Features](#features) · [Quick Start](#quick-start) · [Tech Stack](#tech-stack) · [API Usage](#api-usage) · [Contributing](#contributing)

</div>

---

## Overview

**EduGenAI** is a web application that uses large language models (LLMs) via the NVIDIA NIM API to automatically generate complete German (*Allemand / Almagne*) lessons. Whether you are a teacher looking to scaffold course content or a self-learner building your own curriculum, EduGenAI creates structured lessons with vocabulary, grammar explanations, and exercises — all in one click.

Built with **Laravel** on the backend and a clean JavaScript/CSS frontend, it connects to NVIDIA's hosted AI endpoints for fast, high-quality text generation.

---

## Features

- AI-powered lesson generation — creates full German lessons using top LLMs (Llama 3.3, Mistral Large, Gemma 3)
- Structured output — every lesson includes vocabulary lists, grammar rules, example sentences, and exercises
- Multilingual support — lesson explanations can be generated in Arabic, French, or English for Moroccan learners
- Level-aware — specify CEFR levels (A1 to C2) for appropriate content difficulty
- NVIDIA NIM integration — powered by NVIDIA's optimized inference endpoints for fast responses
- Clean web UI — simple, responsive interface built with Blade templates and vanilla JS

---

## Project Structure

```
EduGenAI/
├── backend/          # Laravel application logic
│   ├── app/
│   │   ├── Http/Controllers/   # Request handlers & AI API calls
│   │   └── Services/           # LLM integration service layer
│   ├── routes/
│   │   └── web.php             # Application routes
│   └── resources/views/        # Blade templates
├── frontend/         # CSS and JavaScript assets
│   ├── css/
│   └── js/
├── vendor/           # Composer dependencies
├── composer.json
└── composer.lock
```

---

## Quick Start

### Prerequisites

- PHP 8.1+
- Composer
- A free [NVIDIA NIM API key](https://build.nvidia.com/models)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/bahasalah255/EduGenAI.git
cd EduGenAI

# 2. Install PHP dependencies
composer install

# 3. Copy and configure environment
cp .env.example .env

# 4. Add your NVIDIA API key to .env
# NVIDIA_API_KEY=nvapi-xxxxxxxxxxxxxxxxxxxx
# NVIDIA_BASE_URL=https://integrate.api.nvidia.com/v1

# 5. Generate application key
php artisan key:generate

# 6. Start the development server
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## AI Models

EduGenAI connects to the following models via [build.nvidia.com](https://build.nvidia.com/models):

| Model | Best For | Endpoint |
|-------|----------|----------|
| `meta/llama-3.3-70b-instruct` | General lesson generation | Free |
| `mistralai/mistral-large-2-instruct` | Multilingual explanations | Free |
| `google/gemma-3-27b-it` | Structured reasoning tasks | Free |

All models are accessed via NVIDIA's OpenAI-compatible API at `https://integrate.api.nvidia.com/v1`.

---

## API Usage

### Generate a lesson (PHP example)

```php
$client = new \GuzzleHttp\Client();

$response = $client->post('https://integrate.api.nvidia.com/v1/chat/completions', [
    'headers' => [
        'Authorization' => 'Bearer ' . env('NVIDIA_API_KEY'),
        'Content-Type'  => 'application/json',
    ],
    'json' => [
        'model'    => 'meta/llama-3.3-70b-instruct',
        'messages' => [
            [
                'role'    => 'user',
                'content' => 'Generate a structured A1-level German lesson on greetings.
                              Include: vocabulary list, grammar note, 3 example sentences,
                              and 2 practice exercises. Explain in French.',
            ],
        ],
        'temperature' => 0.7,
        'max_tokens'  => 1500,
    ],
]);

$lesson = json_decode($response->getBody(), true);
echo $lesson['choices'][0]['message']['content'];
```

### Example output

```
Lektion 1 - Begrusungen (Greetings) | Niveau A1

Vocabulaire / Wortschatz:
- Hallo           -> Bonjour
- Guten Morgen    -> Bonjour (le matin)
- Wie heißen Sie? -> Comment vous appelez-vous ?
- Ich heiße...    -> Je m'appelle...

Grammaire / Grammatik:
Le verbe "heißen" (s'appeler) au present :
  ich heiße | du heißt | er/sie heißt

Exemples:
1. Hallo! Ich heiße Ahmed.
2. Guten Morgen, Frau Muller!

Exercices:
1. Completez: "Wie _____ Sie?" -> Wie heißen Sie?
2. Traduisez: "Je m'appelle Sara." -> Ich heiße Sara.
```

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend framework | Laravel (PHP) |
| Templating | Blade |
| Frontend | Vanilla JS + CSS |
| AI provider | NVIDIA NIM API |
| Package manager | Composer |
| Language models | Llama 3.3 / Mistral Large / Gemma 3 |

---

## Roadmap

- [ ] User authentication and lesson history
- [ ] PDF export of generated lessons
- [ ] Audio pronunciation via NVIDIA Riva TTS
- [ ] Interactive quiz mode after each lesson
- [ ] Support for more languages (Spanish, Italian)
- [ ] Teacher dashboard with lesson library

---

## Contributing

Contributions are welcome. To get started:

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a pull request

Please open an issue first for major changes so we can align on direction.

---

## License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

---

<div align="center">
Built for language learners in Morocco and beyond.
</div>