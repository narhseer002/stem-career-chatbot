# STEM Career Guide

A Laravel and Blade chatbot for undergraduate STEM career exploration. The chatbot ranks curated profiles using subject and activity matches, keeps conversation context in the Laravel session, and explains its suggestions. It runs without an AI API account.

## Local setup

Requires PHP 8.2 or newer, Composer, and PDO for SQLite.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Keep any existing `.env` and SQLite database when repeating setup. Open http://127.0.0.1:8000.

## Deploy to Render

Push the contents of this project directory to a Git repository so the Dockerfile and render.yaml are at the repository root. In Render, create a Blueprint from that repository, or create a Docker Web Service with /up as its health check.

Generate an application key locally with:

```powershell
php artisan key:generate --show
```

Enter the full base64-prefixed value as the APP_KEY secret when Render prompts for it. Keep the same key on future deployments. For a manually created Docker service, APP_KEY is also the only required setting. The container uses Render's PORT and serves the public directory through Apache.

The service uses SQLite on Render's ephemeral filesystem. Each new instance creates the database, runs migrations, and seeds the career catalogue before serving requests. Saved conversations and other runtime changes can be lost on redeploy, restart, or spin-down.

## Demo and tests

Choose **Find career matches**, then enter **final-year student**, **biology and statistics**, and **I enjoy writing code**. Follow up with **What skills do I need for option 1?** or **Compare Data Analyst and Data Scientist**.

Run `php artisan test` to verify the basic page, matching, follow-ups, comparisons, session reset, and input validation. The initial 12 profiles are in `database/seeders/DatabaseSeeder.php`.
