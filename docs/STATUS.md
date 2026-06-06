# Status / Handoff

> Living handoff doc — what's done, what's next, what needs a human. Updated as work proceeds.
> Roadmap lives in [ARCHITECTURE.md](./ARCHITECTURE.md#roadmap).

_Last updated: 2026-06-06 — end of M1._

## Where we are: **M1 (API spine) — DONE** ✅

The agent front door is alive locally: a self-describing Symfony 7.4 + API Platform 4.3 API
on FrankenPHP, backed by Postgres, with a seed catalog.

### Delivered
- **Dockerized dev stack** (`compose.yaml`, `infra/php/`): FrankenPHP app (uid 1000 → host-editable
  files) + Postgres 16. Sidesteps the Codespace's custom PHP build (missing intl/pdo_pgsql).
- **Domain model**: `Agent` (citizen) and `Track` (a recipe — single prompt or runbook — with a
  **stated outcome + success criterion**), plus `TrackKind` enum. Code in `api/src/Entity`, `api/src/Enum`.
- **Self-describing API** (agent-legibility for free): Hydra JSON-LD + **OpenAPI 3.1**.
- **Portable blob**: every track exposes a copy-paste `blob` (paste into any agent → it knows the
  outcome, the recipe, and how to report back). See `Track::getBlob()`.
- **Seed catalog** (`api/src/DataFixtures/AppFixtures.php`): 2 agents (aria, doppler) + 5 exemplar tracks.

### Verified working
- `GET /api/tracks` → 5 tracks, JSON-LD, embedded `createdBy`, `blob` present.
- `GET /api/agents` → aria, doppler.
- `POST /api/tracks` (with `createdBy` IRI) → creates a track. ✔ round-trip.
- OpenAPI 3.1 at `GET /api/docs.jsonopenapi`; Swagger UI at `/api` (Accept: text/html).

## How to run it locally

```bash
docker compose up -d --build          # starts app (:8000) + postgres (:5432)
# first time only — create schema + seed:
docker compose run --rm -e HOME=/tmp app php bin/console doctrine:migrations:migrate --no-interaction
docker compose run --rm -e HOME=/tmp app php bin/console doctrine:fixtures:load --no-interaction

curl -s -H 'Accept: application/ld+json' http://localhost:8000/api/tracks   # the catalog
open http://localhost:8000/api                                             # Swagger UI (in a browser)
```

Console/Composer in-container: `docker compose run --rm -e HOME=/tmp app php bin/console <cmd>`.

## Next: **M2 — Social loop + real-time**
- `Performance` (anecdotal review: outcome ✓/✗, model, tweet-length note — **never** a transcript dump),
  `Vote`, `Feedback`. Ordering by score. Mercure (built into FrankenPHP) pushes the live activity stream.

## Waiting on a human (Mike)
- **Nothing yet.** First human gate is **M3 deploy**: Railway (API + spending cap) and Cloudflare
  (DNS for social-playlist.com). Everything through M2 and most of M3 is local/autonomous.

## Notes / decisions made while building
- FrankenPHP reads its Caddyfile from `/etc/frankenphp/Caddyfile` (not `/etc/caddy/`). Our config is
  plain HTTP on :8000 (no TLS) so running as non-root uid 1000 avoids the root-owned `/data` cert dir.
- Branch: `m1-api-spine`. Checkpoints committed locally; **not pushed** (push is a human gate).
