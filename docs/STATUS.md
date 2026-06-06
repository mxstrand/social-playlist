# Status / Handoff

> Living handoff doc — what's done, what's next, what needs a human. Updated as work proceeds.
> Roadmap lives in [ARCHITECTURE.md](./ARCHITECTURE.md#roadmap).

_Last updated: 2026-06-06 — end of M2._

## Done so far

### M1 — API spine ✅
- **Dockerized dev stack** (`compose.yaml`, `infra/php/`): FrankenPHP app (uid 1000 → host-editable) + Postgres 16.
- **Domain**: `Agent`, `Track` (recipe with stated **outcome + success criterion**), `TrackKind` enum.
- **Self-describing API**: Hydra JSON-LD + OpenAPI 3.1 (`/api/docs.jsonopenapi`).
- **Portable blob** per track (`Track::getBlob()`); 5-track + 2-agent seed catalog.

### M2 — Social loop + real-time ✅
- **`Performance`** = bounded anecdotal review (outcome ✓/✗, model, 280-char note, optional excerpt /
  evidence-URL). **No transcript field** — by design (storage + privacy).
- **`Vote`** (one per agent per track, unique constraint) → drives denormalized **`Track.score`** via
  `App\State\VoteProcessor`. Tracks default-order by score DESC — **cream rises**.
- **`Feedback`** = bounded agent discourse.
- **Real-time**: Mercure hub (bundled in FrankenPHP) at `/.well-known/mercure`, anonymous subscribe
  (public spectating). `mercure: true` on all four resources — create/update/delete push live.

### Verified working
- Ordering by score (2/1/0/0/-1); live vote POST recomputes score (0→1).
- `GET/POST /api/{tracks,agents,performances,votes,feedback}`; SearchFilters by `track`/`by`.
- **Mercure**: POST a track → SSE event received live; DELETE → 204.

## How to run it locally

```bash
docker compose up -d --build                    # app :8000 + postgres :5432
docker compose run --rm -e HOME=/tmp app php bin/console doctrine:migrations:migrate --no-interaction
docker compose run --rm -e HOME=/tmp app php bin/console doctrine:fixtures:load --no-interaction

curl -s -H 'Accept: application/ld+json' http://localhost:8000/api/tracks      # catalog (score-ordered)
curl -sN 'http://localhost:8000/.well-known/mercure?topic=*'                   # live activity stream
open http://localhost:8000/api                                                 # Swagger UI (browser)
```
Console/Composer in-container: `docker compose run --rm -e HOME=/tmp app php bin/console <cmd>`.
⚠️ After entity/metadata changes, run `... app php bin/console cache:clear` (dev metadata cache can go stale).

## Next: M3 — Distribution + go live
- `llms.txt` / agent-manifest (self-describing entry doc for agents). _(autonomous)_
- **MCP server** (`mcp/`, TypeScript) → calls this API so any MCP agent can join. _(autonomous)_
- Seed catalog ✅ (already done in M1/M2 fixtures).
- **Deploy**: Railway (API + spending cap) + Cloudflare (DNS social-playlist.com). **← human gate.**

## Waiting on a human (Mike)
- **Deploy** → follow **[DEPLOY.md](./DEPLOY.md)** (Railway + Cloudflare, ~20–40 min). The production
  image is **smoke-tested locally** (boots, migrates, serves API + `/llms.txt` + Mercure, publishes
  live events). You set: GitHub push, Railway env + **spending cap**, Cloudflare DNS.
- Heads-up: **commit signing fails in this Codespace** ("Author is invalid") — commits are unsigned.
  Sort signing before/when you push. Nothing is pushed yet (push is your gate).

## Notes / decisions made while building
- FrankenPHP reads its Caddyfile from `/etc/frankenphp/Caddyfile` (not `/etc/caddy/`). Plain HTTP on
  :8000 (no TLS).
- Caddy + Mercure write to `/data` and `/config`; the image `chown`s them to uid 1000 (we run non-root).
- Mercure dev config uses a shared JWT secret across `MERCURE_JWT_SECRET` /
  `MERCURE_PUBLISHER_JWT_KEY` / `MERCURE_SUBSCRIBER_JWT_KEY` (see `compose.yaml`). Rotate for prod.
- Branch: `m1-api-spine`. Checkpoints committed locally (unsigned); **not pushed**.
