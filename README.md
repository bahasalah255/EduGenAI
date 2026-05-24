**EduGenAI — Local development quickstart**

This repository contains a Laravel backend and a Vite + React frontend for generating short language lessons using an AI service.

**Structure**
- **backend/**: Laravel application (API endpoints)
- **frontend/**: React app (Vite)

**Prerequisites**
- PHP 8.1+ with common extensions. On Linux install the SQLite PDO driver: `php-sqlite3` / enable `pdo_sqlite`.
- Composer
- Node.js 18+ and npm
- (Optional) NVIDIA API key set as `NVIDIA_API_KEY` in backend `.env` when using the upstream AI integration.

**Backend — quick setup**
1. Install PHP dependencies and copy environment file:

```bash
cd backend
composer install
cp .env.example .env
# Set APP_KEY and NVIDIA_API_KEY in .env (or run artisan key:generate then edit .env)
php artisan key:generate
```

2. If using SQLite (default dev), ensure the driver is installed and create the DB file:

```bash
touch database/database.sqlite
# On Debian/Ubuntu: sudo apt-get install php-sqlite3
```

3. Run the app locally:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

The API endpoint for lesson generation is POST `http://127.0.0.1:8000/api/generate` (see [backend/routes/api.php](backend/routes/api.php)).

**Frontend — quick setup**
1. Install dependencies and run dev server:

```bash
cd frontend
npm install
npm run dev
```

2. Open the app at the displayed Vite URL (usually http://localhost:5173).

**API usage (example)**
You can test the backend directly with curl:

```bash
curl -X POST http://127.0.0.1:8000/api/generate \
  -H "Content-Type: application/json" \
  -d '{"topic":"Present Tense of Regular Verbs"}'
```

Expected response is JSON with keys: `topic`, `lesson`, `sentences`, `exercises`, `solutions_arabic`, `questions`, `answers`.

**Common issues & troubleshooting**
- 500 / Internal Server Error on `/api/generate`: check the Laravel log at `backend/storage/logs/laravel.log` for stack traces. Common causes:
  - Missing or invalid `NVIDIA_API_KEY` when the backend calls the upstream AI provider.
  - PHP missing PDO/SQLite driver (error: "could not find driver"). Fix by installing `php-sqlite3` or configuring a different DB connection.
  - Middleware/bootstrap errors (if you see "Target class [HandleCors] does not exist"), ensure `bootstrap/app.php` imports `Illuminate\Http\Middleware\HandleCors`.

- Timeout when calling upstream AI (e.g. cURL timeout): the backend now includes a longer timeout and retries, but slow or unreachable upstream endpoints will return a 503 JSON message. Check network connectivity and that the provider endpoint is correct.

**Developer notes**
- Routes: see [backend/routes/api.php](backend/routes/api.php).
- Lesson controller: [backend/app/Http/Controllers/LessonController.php](backend/app/Http/Controllers/LessonController.php)
- AI service wrapper: [backend/app/Services/AIService.php](backend/app/Services/AIService.php)
- Frontend input component: [frontend/src/componenets/Input.jsx](frontend/src/componenets/Input.jsx)

If you'd like, I can also:
- Add a small Postman collection or a runnable test script to hit the API.
- Add CI checks that lint/build the frontend and backend.
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