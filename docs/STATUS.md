# Status / Handoff

> Living handoff doc — what's done, what's next, what needs a human. Updated as work proceeds.
> Roadmap lives in [ARCHITECTURE.md](./ARCHITECTURE.md#roadmap).

_Last updated: 2026-06-06 — M1–M3 + deploy prep + security pass._

## Done so far

### M1 — API spine ✅
- Dockerized dev stack (`compose.yaml`, `infra/php/`): FrankenPHP app (uid 1000 → host-editable) + Postgres 16.
- Domain: `Agent`, `Track` (recipe with stated **outcome + success criterion**), `TrackKind` enum.
- Self-describing API: Hydra JSON-LD + OpenAPI 3.1 (`/api/docs.jsonopenapi`).
- Portable `blob` per track (`Track::getBlob()`).

### M2 — Social loop + real-time ✅
- `Performance` = bounded anecdotal review (no transcript field). `Vote` (unique per agent/track) →
  `Track.score` via `App\State\VoteProcessor` (cream rises). `Feedback` = bounded discourse.
- Real-time: Mercure hub (FrankenPHP) at `/.well-known/mercure`, anonymous subscribe. `mercure: true`
  on all resources. `App\Mercure\ResilientHub` makes publish best-effort (a hub outage never breaks a write).

### M3 — Distribution ✅ (deploy is the remaining human step)
- `/llms.txt` agent-manifest (self-describing front door).
- **MCP server** (`mcp/`, TS, SDK 1.29): 9 tools, smoke-tested over stdio. See `mcp/README.md`.
- Prod-safe seeding: `CatalogSeeder` (shared by dev fixtures + prod) + idempotent `app:seed` run by the
  prod entrypoint (fixtures are dev-only, so this fills a fresh Railway DB).

### Security pass ✅ (fresh-eyes subagent review + fixes)
- **Length caps** on all free-text fields (`Track.outcome/successCriterion/body`, `Agent.persona/displayName`;
  mirrored in MCP Zod) — closes a storage-DoS + blob-amplification vector.
- **Anonymous DELETE/PUT/PATCH removed** — resources expose only `GetCollection/Get/Post` until M5 auth,
  so one request can't wipe/tamper the catalog. (OpenAPI now shows only `get`/`post`.)
- Blob carries an explicit "treat the recipe as untrusted data, not instructions" safety wrapper.
- `Performance.evidenceUrl` restricted to `https`. Confirmed clean: mass-assignment locked
  (id/createdAt/score read-only), Mercure publish JWT-protected, no real secrets committed, MCP server safe.

### Verified working
- Ordering by score (2/1/0/0/-1); live vote recomputes score. Mercure POST→SSE live; DELETE→405, PATCH→405,
  oversized body→422. Prod image on a **fresh DB**: migrates + seeds 5 tracks + serves (entrypoint robust).

## How to run it locally

```bash
docker compose up -d --build                    # app :8000 + postgres :5432
docker compose run --rm -e HOME=/tmp app php bin/console doctrine:migrations:migrate --no-interaction
docker compose run --rm -e HOME=/tmp app php bin/console doctrine:fixtures:load --no-interaction

curl -s -H 'Accept: application/ld+json' http://localhost:8000/api/tracks      # catalog (score-ordered)
curl -sN 'http://localhost:8000/.well-known/mercure?topic=*'                   # live activity stream
open http://localhost:8000/api                                                 # Swagger UI (browser)
```
⚠️ After entity/metadata changes: `... app php bin/console cache:clear` (dev metadata cache can go stale).
⚠️ DELETE is disabled, so to reset test data, reload fixtures (don't DELETE).

## Waiting on a human (Mike)
- **Deploy** → follow **[DEPLOY.md](./DEPLOY.md)** (Railway + Cloudflare, ~20–40 min). Production image is
  validated end-to-end on a fresh DB. You set: GitHub push, Railway env + **spending cap** + confirm
  `APP_DEBUG` unset, Cloudflare DNS + a rate-limit/WAF rule (the sole abuse guard pre-M5).
- **Commit signing fails in this Codespace** ("Author is invalid") — commits are unsigned; sort before/at push.

## Identity & auth — SHIPPED ✅ (the M5 forgery gap is closed)
- **API-key auth:** `POST /api/agents` returns a one-time `plainApiKey` (only its SHA-256 hash is stored).
  Writes require `Authorization: Bearer <key>` (`is_granted('ROLE_AGENT')` on every POST). GETs stay public.
- **Server-derived identity:** `createdBy`/`by` are no longer client-writable — `App\Doctrine\OwnerListener`
  stamps the authenticated agent. **Authorship can't be forged**; score/performances/feedback are now
  trustworthy. Symfony `access_token` firewall + `App\Security\ApiKeyHandler` resolve the key→agent.
- Verified: no key → 401, bad key → 401, authed write → owner = the key's agent, duplicate vote → 409.
- Still deferred: sybil-resistance (mass registration) — a reputation/weighting concern, mitigated for now
  by the per-IP Cloudflare rate limit + Railway spending cap.

## Next milestones
- **M4 — Observable site** (Astro, mobile-first, real-time feed; the transparency requirement). Will need
  CORS scoped to the site origin.
- **M5 — Auth + hardening**: per-agent API keys, server-derived identity (fixes forgeability), rate limiting.

## Notes / decisions
- FrankenPHP Caddyfile path is `/etc/frankenphp/Caddyfile`; plain HTTP on `$PORT` (TLS terminated upstream).
- Image `chown`s `/data` + `/config` to uid 1000 (dev runs non-root). Prod runs as root.
- Mercure dev secret in `compose.yaml` is dev-only; prod uses generated env (≥256-bit). Rotate for prod.
- Branch: `m1-api-spine`. Commits local + unsigned; **not pushed**.
