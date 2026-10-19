# ADR 0001 — Modular monolith with contract-only module boundaries

- Status: Accepted
- Date: 2026-10-19
- Deciders: backend team
- Related: `docs/architecture.md` §3.3, Phase 1 authentication implementation

## Context

HadirMetrik Phase 1 introduces the first real domain code (authentication) on top
of the Phase 0 skeleton. The architecture document calls for a modular monolith
with explicit module boundaries, but leaves the concrete layout open. Without a
rule, controllers, models, and helpers would import one another freely and the
boundaries would erode before later phases (attendance, payroll) add pressure.

## Decision

1. Domain code lives in `app/Modules/<Module>/` with four layers:
   - `Contracts/` — the module's only public surface (`Contracts/Data` holds
     readonly DTOs).
   - `Application/` — use cases and application exceptions.
   - `Infrastructure/` — Eloquent models, adapters, framework-facing services.
   - `Http/` — controllers, form requests, response shaping.
2. Cross-module calls go through `Contracts` only. A module must never import
   another module's `Application`, `Infrastructure`, or `Http`. Enforced by
   `tests/Architecture/ModuleBoundaryTest.php`.
3. Cross-module data crosses as readonly DTOs, never Eloquent models.
4. Each module exposes a `[Module]ServiceProvider` at its root that binds its
   contracts to implementations. Providers only register bindings, policies, and
   migrations; they do **not** load routes.
5. Providers are registered explicitly in `bootstrap/providers.php` (Laravel 13
   does not auto-discover application providers).
6. **Routes stay centralized** in `routes/`, and **migrations stay centralized**
   in `database/migrations/`. Modules own their models, not the HTTP entrypoint
   or the schema runner. This keeps `php artisan migrate` and route listing
   simple and matches how the team reads the application.
7. Framework dependencies (Sanctum, hashing, DB transactions) sit behind ports
   in `Contracts` and are implemented under `Infrastructure/Services`.

Phase 1 modules:

- `Identity` — accounts, passwords, tokens, and the auth use cases.
- `Employees` — employee directory and invitation codes.
- `Audit` — security event recording.
- `app/Shared` — not a module: response envelope, domain exception base, and the
  cross-cutting `TransactionManager` port.

## Consequences

- Positive: boundaries are explicit and mechanically checked; swapping Sanctum
  or the hasher is an adapter change; use cases are unit-testable with fakes.
- Positive: later modules (Attendance, Payroll) have a pattern to copy.
- Negative: more files and indirection than a plain Laravel application.
- Negative: centralized routes and migrations mean a module is not fully
  self-contained; accepted for operational simplicity.
- Follow-up: `docs/openapi/auth.yaml` must be updated whenever a route's
  request or response shape changes.
