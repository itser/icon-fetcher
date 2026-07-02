# PSVGameStudio

CAS.AI interview prep — App Icon Fetcher (Laravel modular monolith).

## Run

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

- App: http://localhost:8081
- **Web UI:** http://localhost:8081/app-icons

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

### curl examples

```bash
# Success
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"com.zhiliaoapp.musically"}'

# Invalid bundle_id (422)
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"not-valid"}'

# Get task
curl -s http://localhost:8081/api/v1/app-icons/tasks/1
```

Response shape: `{ "data": { "id", "bundle_id", "status", "apple_icon_url", "google_icon_url", "errors" } }`.

## Plans

- `.cursor/plans/architecture-plan.md`
- `.cursor/plans/implementation-plan.md`
