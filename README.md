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

Expected: four services running (`app`, `nginx`, `redis`, `horizon`) and **21 tests** passing.

```bash
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"com.nianticlabs.pokemongo"}' | jq .
```

Open http://localhost:8081/app-icons — enter a bundle ID and click **Fetch icons**.

## Run (after installation)

```bash
docker compose up -d
```

- App: http://localhost:8081
- Web UI: http://localhost:8081/app-icons
- Horizon: http://localhost:8081/horizon

### Stop

```bash
docker compose down
```

## Web UI

Minimal Blade page (no build step): enter `bundle_id`, click **Fetch icons** or **List tasks**.

- Calls JSON API via `fetch()`
- Shows Apple / Google images or «Icon not found»
- Partial success: one icon + per-store `errors` (e.g. `google: Icon not found`)
- Async: first fetch may show `pending` — use **List tasks** or poll GET (no auto-polling in UI)

## API (`/api/v1`)

| Method | Path | Description |
| ------ | ---- | ----------- |
| GET | `/api/v1/app-icons/tasks` | List all tasks |
| POST | `/api/v1/app-icons/tasks` | Create task, fetch icons |
| GET | `/api/v1/app-icons/tasks/{id}` | Single task |

Response shape: `{ "data": { "id", "bundle_id", "status", "apple_icon_url", "google_icon_url", "errors" } }`.

POST status: `200` + `completed` when result is ready; `202` + `pending` only when a background job was queued.

### curl examples

```bash
# Both stores
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"com.nianticlabs.pokemongo"}' | jq .

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

## Caching

Repeat `bundle_id` within TTL reuses cached icon URLs — **no queue job**, no HTTP to Apple/Google.

- Key: `app-icon:{bundle_id}` in Redis (`CACHE_STORE=redis` in `.env.example`)
- TTL: 1 hour (`APP_ICON_CACHE_TTL`, optional)
- A new task row is still created (history); only fetch is skipped
- Cache hit → `200` + `completed` even when `QUEUE_CONNECTION=redis`

## Queue (async)

`.env.example` uses `QUEUE_CONNECTION=redis`. Horizon runs in the `horizon` service.

- First request for a `bundle_id`: POST → `202`, `pending` → job in Horizon → `completed`
- Cached `bundle_id`: POST → `200`, `completed` immediately (no Horizon job)
- For sync behaviour: set `QUEUE_CONNECTION=sync` in `app/.env` and restart

## Stack

- Laravel 12, `nwidart/laravel-modules`
- Apple: iTunes Lookup API (`artworkUrl512`)
- Google Play: HTML scrape (`og:image`)
- SQLite, Redis (cache + queue), file session
