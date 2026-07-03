# App Icon Fetcher

CAS.AI test task — Laravel modular monolith. Enter a mobile app Bundle ID, get icon URLs from Apple App Store and Google Play.

**Time spent:** 4 hours

**Repository:** https://github.com/itser/icon-fetcher

## Prerequisites

- Git
- Docker and Docker Compose
- No local PHP or Composer required

## Quick start (from scratch)

### 1. Clone

```bash
git clone https://github.com/itser/icon-fetcher.git
cd icon-fetcher
```

### 2. Build and install

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp -n .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app touch database/database.sqlite
docker compose exec app php artisan migrate
docker compose up -d
```

The final `docker compose up -d` restarts `horizon` — it may exit on first boot if `vendor/` is not installed yet.

### 3. Verify

```bash
docker compose ps
docker compose exec app php artisan test
```

Expected: four services running (`app`, `nginx`, `redis`, `horizon`) and all tests passing.

```bash
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"org.telegram.messenger"}' | jq .
```

Open http://localhost:8081/app-icons — enter a bundle ID and click **Fetch icons**.

## Run (after installation)

```bash
docker compose up -d
```

- App: http://localhost:8081
- Web UI: http://localhost:8081/app-icons
- Horizon (async mode): http://localhost:8081/horizon

### Stop

```bash
docker compose down
```

## Web UI

Minimal Blade page (no build step): enter `bundle_id`, click **Fetch icons** or **List tasks**.

- Calls JSON API via `fetch()`
- Shows Apple / Google images or «Icon not found»
- Partial success: one icon + per-store `errors` (e.g. `google: Icon not found`)

## API (`/api/v1`)

| Method | Path | Description |
| ------ | ---- | ----------- |
| GET | `/api/v1/app-icons/tasks` | List all tasks |
| POST | `/api/v1/app-icons/tasks` | Create task, fetch icons (sync → `completed`) |
| GET | `/api/v1/app-icons/tasks/{id}` | Single task |

Response shape: `{ "data": { "id", "bundle_id", "status", "apple_icon_url", "google_icon_url", "errors" } }`.

### curl examples

```bash
# Both stores
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"com.zhiliaoapp.musically"}' | jq .

# Partial success (Apple only)
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"com.apple.MobileSMS"}' | jq .

# Invalid bundle_id (422)
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"not-valid"}' | jq .

# Get task
curl -s http://localhost:8081/api/v1/app-icons/tasks/1 | jq .
```

## Async mode (optional)

Default queue is `sync` — POST returns `200` with icons immediately.

To enable background jobs:

1. Set `QUEUE_CONNECTION=redis` in `app/.env`
2. Restart: `docker compose up -d`
3. POST returns `202` with `status: pending`; poll GET until `completed`

Horizon worker runs in the `horizon` service.

## Stack

- Laravel 12, `nwidart/laravel-modules`
- Apple: iTunes Lookup API (`artworkUrl512`)
- Google Play: HTML scrape (`og:image`)
- SQLite, file cache/session, sync queue by default
