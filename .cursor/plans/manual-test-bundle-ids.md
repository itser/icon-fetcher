# Manual test — bundle IDs

Use after `docker compose up -d`, `migrate`, and opening http://localhost:8081/app-icons (or curl).

Same `bundle_id` is sent to **Apple iTunes Lookup** and **Google Play** (`play.google.com/store/apps/details?id=…`).

**Important:** many cross-platform apps use **different** bundle IDs on iOS vs Android (e.g. Telegram). Only IDs listed under **Both stores** return icons from both APIs with one input.

## Both stores — expect both icons, `errors: []`

Verified: same `bundle_id` exists in iTunes Lookup and on Play Store.

| bundle_id | App |
| --------- | --- |
| `com.zhiliaoapp.musically` | TikTok |
| `com.nianticlabs.pokemongo` | Pokémon GO |
| `com.rovio.baba` | Angry Birds 2 |
| `com.imangi.templerun` | Temple Run |

## Apple only — expect `apple_icon_url`, `google_icon_url: null`, error for Google

| bundle_id | App | Note |
| --------- | --- | ---- |
| `com.apple.MobileSMS` | Messages | System app, not on Play Store |
| `ph.telegra.Telegraph` | Telegram (iOS package) | Android uses `org.telegram.messenger` |
| `com.supercell.magic` | Clash of Clans (iOS) | Android uses `com.supercell.clashofclans` |
| `com.duolingo.DuolingoMobile` | Duolingo (iOS) | Android uses `com.duolingo` |

## Google Play only — expect `google_icon_url`, `apple_icon_url: null`, error for Apple

| bundle_id | App | Note |
| --------- | --- | ---- |
| `org.telegram.messenger` | Telegram (Android package) | iOS uses `ph.telegra.Telegraph` |
| `com.supercell.clashofclans` | Clash of Clans (Android) | iOS uses `com.supercell.magic` |
| `com.duolingo` | Duolingo (Android) | iOS uses `com.duolingo.DuolingoMobile` |
| `com.instagram.android` | Instagram (Android) | iOS uses `com.burbn.instagram` |

## Neither store — expect both urls null, errors for both

| bundle_id | Note |
| --------- | ---- |
| `com.example.totally.fake.app` | Valid format, app does not exist |

## Validation — expect `422`, no task created

| Input | Note |
| ----- | ---- |
| `not-valid` | Fails `bundle_id` regex |
| `` (empty) | Required field |

## Async mode

`.env.example` has `QUEUE_CONNECTION=redis`. Horizon worker: http://localhost:8081/horizon

1. POST a bundle from **Both stores** → `202`, `status: pending`
2. Poll `GET /api/v1/app-icons/tasks/{id}` until `completed` + urls (or use **List tasks** in UI)
3. Job visible in Horizon on first fetch only

## Cache

1. Fetch the same `bundle_id` again within ~1 hour
2. Expect `200`, `completed` immediately — **no new job** in Horizon
3. New task row in **List tasks** (history), but no external HTTP

## Quick curl

```bash
# Both stores
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"com.nianticlabs.pokemongo"}' | jq .

# Apple only (partial)
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"ph.telegra.Telegraph"}' | jq .

# Google only (partial)
curl -s -X POST http://localhost:8081/api/v1/app-icons/tasks \
  -H 'Content-Type: application/json' \
  -d '{"bundle_id":"org.telegram.messenger"}' | jq .
```

> Store catalogs change over time. If a row stops matching expectations, pick another ID from the same section or update this file.
