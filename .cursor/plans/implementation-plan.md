# App Icon Fetcher — Implementation Plan

Based on: [architecture-plan.md](architecture-plan.md)

Current state: 3a + minimal web UI done. **Next: 3b — ProcessAppIconTaskJob.**

## Principles

- **TDD:** write a failing test first (red), then minimal implementation (green), then refactor
- **API versioning:** all endpoints under prefix `/api/v1`
- **Two phases:** fully sync first, then migrate to Redis queues
- **Job-ready design:** all business logic lives in the service; controller and job are thin wrappers
- **Layers:** Controller → Service → Repository (controller must not call repository)

### Layers (module `AppIcon`)

```
Controller  →  Service  →  Repository
                 ↓
            Contracts (Apple / Google)
```

| Layer          | Responsibility                                                 |
| -------------- | -------------------------------------------------------------- |
| **Controller** | HTTP: FormRequest, service call, API Resource                  |
| **Service**    | Business logic: create task, fetch icons, update status/result |
| **Repository** | CRUD for `app_icon_tasks` (called only from service)           |

Ports and adapters (hexagonal): Service depends on Contracts; adapters live in `AppleStore`/`GooglePlay`.

### Naming

| Entity          | Class                     |
| --------------- | ------------------------- |
| Controller      | `AppIconTaskController`   |
| Service         | `AppIconTaskService`      |
| Repository      | `AppIconTaskRepository`   |
| Model           | `AppIconTask`             |
| Job             | `ProcessAppIconTaskJob`   |
| FormRequest     | `StoreAppIconTaskRequest` |
| Resource        | `AppIconTaskResource`     |
| Status enum     | `AppIconTaskStatus`       |
| Apple contract  | `AppleIconProvider`       |
| Google contract | `GooglePlayIconProvider`  |
| Apple adapter   | `AppleStoreIconAdapter`   |
| Google adapter  | `GooglePlayIconAdapter`   |

API fields (per task spec): `bundle_id`, `apple_icon_url`, `google_icon_url`, `errors`.

### Folder structure

```
AppIcon/
  Http/Controllers/AppIconTaskController.php
  Http/Requests/StoreAppIconTaskRequest.php
  Http/Resources/AppIconTaskResource.php
  Services/AppIconTaskService.php
  Repositories/AppIconTaskRepository.php
  Models/AppIconTask.php
  Enums/AppIconTaskStatus.php
  Jobs/ProcessAppIconTaskJob.php
  Contracts/AppleIconProvider.php
  Contracts/GooglePlayIconProvider.php
```

### Job-ready design

```php
// Controller (phase 1) — service only
$task = $this->service->createAndFetch($bundleId);

// Job (phase 1b / phase 2) — delegates to execute()
public function handle(AppIconTaskService $service): void
{
    $service->execute($this->taskId);
}
```

**Rules:**

- `createAndFetch()` — creates task via repository, calls `execute()`, returns task
- `execute($taskId)` — fetch icons, update status/result
- Controller → service only, never repository
- Job → `execute()` only

**Phase 1 implementation order (task API):**

1. Migration + model + enum
2. Controller + FormRequest + Resource + routes
3. Service + Repository + DTO (wire controller to service; feature tests go green)

**Phase 1 runtime order (after step 3):**

1. Controller calls `service->createAndFetch()` (sync, result in POST response)
2. Wrap in `ProcessAppIconTaskJob` (still sync)
3. Phase 2: `QUEUE_CONNECTION=redis`, POST → `202`, polling via GET

## Phases

| Phase        | Queue   | POST response                     | Docker               |
| ------------ | ------- | --------------------------------- | -------------------- |
| 1 — Sync MVP | `sync`  | `200`, `status: completed` + urls | `app` + `nginx`      |
| 2 — Async    | `redis` | `202`, `status: pending`          | + `redis`, `horizon` |

Tests (`phpunit.xml`) always use `QUEUE_CONNECTION=sync`.

## Target architecture

```mermaid
flowchart LR
    Client --> Controller
    Controller --> Service
    Service --> Repository
    Service --> AppleContract
    Service --> GoogleContract
    AppleContract --> AppleAdapter
    GoogleContract --> GoogleAdapter
    AppleAdapter --> iTunesAPI
    GoogleAdapter --> GooglePlay
    Repository --> TaskStore
    Controller --> QueueJob
    QueueJob --> Service
    QueueJob -.->|"phase 2"| Redis
    Horizon -.->|"phase 2"| Redis
```

## API (all under `/api/v1`)

| Method | Path                           | Phase 1 (sync)          | Phase 2 (async)      |
| ------ | ------------------------------ | ----------------------- | -------------------- |
| GET    | `/api/v1/app-icons/tasks`      | `200`, list of tasks    | `200`, list of tasks |
| POST   | `/api/v1/app-icons/tasks`      | `200`, completed + urls | `202`, pending       |
| GET    | `/api/v1/app-icons/tasks/{id}` | `200`, completed + urls | `200`, status + urls |

Routing: `bootstrap/app.php` → prefix `/api`, module `AppIcon` → prefix `/v1`.

## Web UI

| URL | Description |
| --- | ----------- |
| `GET /app-icons` | Minimal Blade page: fetch icons by `bundle_id`, list tasks, show images or per-store errors |

UI calls the JSON API via `fetch()` — no separate web controller; API contract unchanged.

Files: `routes/web.php`, `resources/views/app-icons/index.blade.php`.

## MVP decisions

- **Task storage:** SQLite, table `app_icon_tasks`
- **Status enum:** `pending`, `processing`, `completed`, `failed`
- **Frontend:** minimal Blade UI at `/app-icons` (fetch to JSON API)
- **Apple:** iTunes Lookup API
- **Google Play:** HTTP fetch + HTML parsing
- **bundle_id validation:** `^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)+$`

---

## Phase 1 — Sync MVP (TDD)

### 0. Bootstrap — done

- [x] Install `nwidart/laravel-modules`
- [x] Register API routing: prefix `/api`
- [x] Generate modules: `AppIcon`, `AppleStore`, `GooglePlay`
- [x] Create `app/Shared/` (DTO, Exceptions)

---

### 1. Adapter contract tests → contracts & adapters — done

**Tests (red):**

- [x] `AppleIconProvider` resolves to `AppleStoreIconAdapter`
- [x] `GooglePlayIconProvider` resolves to `GooglePlayIconAdapter`

**Implementation (green):**

- [x] Contracts in `AppIcon/Contracts/`
- [x] Adapters in `AppleStore`, `GooglePlay`
- [x] DI bindings in each store module ServiceProvider

---

### 2. Integration tests → store adapters — done

**Tests (red) with `Http::fake` + fixtures in `tests/Fixtures/`:**

- [x] `AppleStoreIconAdapter`: fixture JSON → icon url
- [x] `AppleStoreIconAdapter`: empty results → error
- [x] `GooglePlayIconAdapter`: fixture HTML → icon url
- [x] `GooglePlayIconAdapter`: 404 / timeout → error

**Implementation (green):**

- [x] Apple: iTunes Lookup, `artworkUrl512`
- [x] Google: Play Store HTML, og:image
- [x] Timeout 3s, graceful error mapping
- [x] Module configs: `applestore.php`, `googleplay.php` (`mergeConfigFrom` in ServiceProviders)

---

### 3a. Feature tests → task API (sync)

**Tests (red) — done:**

- [x] `POST /api/v1/app-icons/tasks` valid `bundle_id` → `200`, `status: completed`, urls
- [x] `POST` invalid `bundle_id` → `422`
- [x] `GET /api/v1/app-icons/tasks/{id}` → `200`, completed, urls
- [x] `GET` partial success → completed, one url + errors
- [x] `GET` list of tasks → `200`, `data[]`
- [x] `GET` unknown id → `404`

---

#### 3a.1. Persistence layer — done

**Implementation:**

- [x] Migration `app_icon_tasks` (`bundle_id`, `status`, `apple_icon_url`, `google_icon_url`, `errors`, timestamps)
- [x] Model `AppIconTask` (fillable, casts: `status` → enum, `errors` → array)
- [x] Enum `AppIconTaskStatus` (`pending`, `processing`, `completed`, `failed`)

**Verify:** `php artisan migrate` in Docker; model usable in `tinker`.

---

#### 3a.2. HTTP layer — done

**Implementation:**

- [x] `StoreAppIconTaskRequest` — `bundle_id` regex validation
- [x] `AppIconTaskResource` — JSON under `data`: `id`, `bundle_id`, `status`, urls, `errors`
- [x] `AppIconTaskController` — `index()`, `store()`, `show()` (calls service)
- [x] Routes in `AppIcon/routes/api.php`: `GET/POST app-icons/tasks`, `GET tasks/{id}`

---

#### 3a.3. Business layer (feature tests green) — done

**Implementation:**

- [x] `AppIconTaskRepository` — CRUD via model (not `DB::` facade)
- [x] `AppIconTaskService` — `createAndFetch()`, `find()`, `list()`, `execute()`
- [x] DTO `IconFetchResult` in `app/Shared/DTO/`
- [x] Wire controller → `service->createAndFetch()` / `service->find()`

**Flow (sync, no job):**

1. POST → controller → `service->createAndFetch()` → `completed` → `200` with urls
2. GET → controller → `service->find($id)` → result

**Verify:** `php artisan test tests/Feature/AppIconTaskApiTest.php` — all green.

---

#### 3a.4. Minimal web UI — done

**Implementation:**

- [x] `GET /app-icons` — Blade page (`resources/views/app-icons/index.blade.php`)
- [x] **Fetch icons** — `POST /api/v1/app-icons/tasks` via `fetch()`
- [x] **List tasks** — `GET /api/v1/app-icons/tasks` via `fetch()`
- [x] Show Apple / Google `<img>` or «Icon not found»; display `errors` per store (partial success, no crash)

**Verify:** open http://localhost:8081/app-icons after `docker compose up` + `migrate`.

---

### 3b. Wrap in job (still sync)

**Tests (red):**

- Feature tests stay green (sync job runs inline)
- `ProcessAppIconTaskJob` delegates to `AppIconTaskService::execute()`

**Implementation (green):**

- Controller: `create task` + `dispatch(ProcessAppIconTaskJob)` instead of `createAndFetch()`
- Or `createAndFetch()` dispatches internally — service API unchanged for job path
- `QUEUE_CONNECTION=sync`

---

### 4. Docker & README

- `docker compose up` + `migrate` + `test` — all green
- README: launch, web UI, curl examples (success, partial, invalid), time spent
- `.env`: `QUEUE_CONNECTION=sync`

---

## Phase 2 — Redis queues

### 5. Migrate to Redis + Horizon

**Tests:**

- Existing tests stay green (`sync` in phpunit.xml)
- Feature test: job pushed to redis queue (`Queue::fake`)

**Docker:**

- `redis` (Redis 7)
- `horizon` — worker + UI at `/horizon`
- `redis` extension in `app/docker/Dockerfile`

**Config:**

- `.env`: `QUEUE_CONNECTION=redis`, `REDIS_HOST=redis`
- `laravel/horizon`, supervisors for `ProcessAppIconTaskJob`

**Behavior (async):**

1. POST → task `pending` → dispatch job → `202`
2. Horizon → `ProcessAppIconTaskJob` → `service->execute()` → `completed`
3. GET → poll `pending`/`processing` → result

> Service and job body unchanged. Only queue driver and POST response change.

---

## Commit order (TDD)

**Phase 1:**

1. [x] `add modules scaffold and api v1 routing`
2. [x] `add adapter contract tests and provider bindings`
3. [x] `add store adapter integration tests and implementation`
4. [x] `add task api feature tests with sync service call`
5. [x] `add app icon tasks migration model and status enum`
6. [x] `add task api controller request resource and routes`
7. [x] `add task service repository and IconFetchResult dto`
8. [x] `add list app icon tasks endpoint through service layers`
9. [x] `add minimal blade ui for app icon fetcher`
10. [ ] `wrap fetch in ProcessAppIconTaskJob delegating to service` ← **next**
11. [ ] `update readme with launch instructions`

**Phase 2:**

12. [ ] `add redis and horizon to docker compose`
13. [ ] `switch queue to redis and add horizon config`
