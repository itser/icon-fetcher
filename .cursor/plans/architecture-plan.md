# Architecture Plan

## Approach

- Hexagonal architecture for modular monolith
- `nwidart/laravel-modules` for module boundaries

## Modules

- `AppIcon` — API entrypoint; orchestrates via Service layer
- `AppleStore` — infrastructure adapter for Apple App Store
- `GooglePlay` — infrastructure adapter for Google Play
- `app/Shared` — cross-module DTO and Exceptions

## Hexagonal boundaries

- Ports in `AppIcon`
- Adapters in `AppleStore` and `GooglePlay`

## Async flow

- Queue-based tasks
- Submit endpoint creates a task and returns `202 Accepted`
- Worker fetches Apple and Google icons in background
- Result endpoint returns task status and final or partial payload

## Testing

- Feature tests as primary verification
- Adapter contract tests: each port has a concrete adapter implementation
- `Http::fake` for external store integrations
