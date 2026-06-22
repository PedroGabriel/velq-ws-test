# Velq flagship demo: a default Laravel 13 app, unmodified

This is a clean, default Laravel 13 application that exercises the whole Velq
platform - Database, Cache, Files, Queue, Mail and live WebSockets - using only
standard Laravel features wired by the environment Velq injects at runtime.
There is no Velq-specific code: no custom clients, no adapter classes, no
internal control routes. It proves the claim "Velq runs any default Laravel app
unmodified and still uses the whole platform."

The app is a live guestbook: post a message (optionally with an image), see it
appear instantly for every connected visitor, with no page refresh.

## How each platform feature maps to standard Laravel

| Feature | Standard Laravel used | Wired by |
| --- | --- | --- |
| Database | Eloquent on SQLite, ULID keys (`HasUlids`) | `DB_CONNECTION=sqlite`, `DB_DATABASE=/data/database.sqlite` (per-instance disk) |
| Auth / Sessions | Built-in `Auth`, database session driver | `SESSION_DRIVER=database` on the same SQLite |
| Files | `Storage::disk('s3')` | `FILESYSTEM_DISK=s3` + `AWS_*` + `AWS_ENDPOINT` (Velq R2) |
| Queue | `ShouldQueue` job, database queue | `QUEUE_CONNECTION=database` |
| Mail | `Mail::send(...)` over SMTP | `MAIL_MAILER=smtp` + `MAIL_HOST` / `MAIL_PORT` (Velq mail catcher) |
| Cache | `Cache::remember(...)` | `CACHE_STORE=database` |
| WebSockets | Broadcasting (`pusher` driver) + Laravel Echo | `PUSHER_APP_*` + `PUSHER_HOST` / `PUSHER_PORT` / `PUSHER_SCHEME` |

All of these are configured in `.env.example` and the default `config/*.php`
files. Velq injects the connection details (hosts, ports, scoped credentials)
at runtime, so the same source runs both locally and on the platform.

## WebSockets

- The server broadcasts `App\Events\MessagePosted` over the standard `pusher`
  broadcaster on a public `chat` channel. It reaches the Velq edge REST API at
  `PUSHER_HOST` / `PUSHER_PORT`.
- The client uses Laravel Echo (`resources/js/echo.js`). The Pusher app key is
  injected by Velq at runtime, after the asset build, so it is server-rendered
  into the page (`window.Velq.broadcasting.key`) rather than baked into the
  bundle. Echo connects back to the app's own origin (`/app/{key}`), so it
  needs no extra host or port.

## Run locally

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
php artisan serve
```

For the queue and live broadcasting locally, also run `php artisan queue:work`
and point the `PUSHER_*` and `MAIL_*` env at a local Pusher-compatible server
and SMTP catcher. On Velq this is all wired automatically.

## Deploy

`velq.json` is the deploy manifest (app name, env, build, bindings, resources).
It is configuration, not application code.
