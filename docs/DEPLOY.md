# Deploy Runbook — Railway + Cloudflare

> Goal: get the API live at **https://social-playlist.com**.
> Stack: one FrankenPHP container (API + Mercure hub) on **Railway** + managed **Postgres**,
> fronted by **Cloudflare** (DNS, free egress, edge). ~20–40 min.
>
> ✅ The production image (`infra/php/Dockerfile.prod`) is **smoke-tested locally**: it boots, runs
> migrations, serves the API + `/llms.txt` + the Mercure hub, and publishes real-time events.

---

## 0. Prerequisite — push the code to GitHub

Railway deploys from the repo. The work is on branch **`m1-api-spine`** (4–5 unsigned local commits).

```bash
# from the Codespace terminal (you, so commit signing works):
git push -u origin m1-api-spine
# then either open a PR → merge to main, or deploy the branch directly (step 1 lets you pick).
```

⚠️ **Commit signing**: signing failed for me in the Codespace ("Author is invalid"), so my commits are
unsigned. If your push is rejected for signing, either sign on push or temporarily allow unsigned.

---

## 1. Railway — create the service

1. https://railway.app → **New Project** → **Deploy from GitHub repo** → `mxstrand/social-playlist`.
   - Pick the branch you pushed (`m1-api-spine` or `main`).
2. Railway reads **`railway.json`** → builds with `infra/php/Dockerfile.prod` automatically. Let the
   first build run (it'll fail health checks until env + DB are set — that's expected; continue).

## 2. Railway — add Postgres

1. In the project: **New** → **Database** → **PostgreSQL**. (Railway provisions it + a `DATABASE_URL`.)

## 3. Railway — set environment variables

On the **API service** → **Variables**. Generate secrets first:

```bash
openssl rand -hex 16   # → APP_SECRET
openssl rand -hex 32   # → the Mercure secret (MUST be ≥256 bits / 64 hex chars — shorter = 500s)
```

| Variable | Value |
|---|---|
| `APP_ENV` | `prod` |
| `APP_SECRET` | _(the `rand -hex 16` value)_ |
| `DATABASE_URL` | `${{Postgres.DATABASE_URL}}` _(Railway reference — pick your Postgres service)_ |
| `MERCURE_PUBLIC_URL` | `https://social-playlist.com/.well-known/mercure` |
| `MERCURE_JWT_SECRET` | _(the `rand -hex 32` value)_ |
| `MERCURE_PUBLISHER_JWT_KEY` | _(**same** `rand -hex 32` value)_ |
| `MERCURE_SUBSCRIBER_JWT_KEY` | _(**same** `rand -hex 32` value)_ |
| `TRUSTED_PROXIES` | `127.0.0.1,REMOTE_ADDR` _(trust the upstream proxy)_ |

- **Do NOT set `MERCURE_URL`** — the entrypoint derives it from the runtime `$PORT`.
- **Leave `APP_DEBUG` unset** (prod defaults it off). Never set it to `1` — that leaks stack traces.
- The three `MERCURE_*` JWT values must be **identical**.
- `DATABASE_URL` from Railway works as-is (Doctrine auto-detects the server version).

After saving, Railway redeploys. Watch **Deploy Logs** — you should see:
`[entrypoint] running database migrations...` → `Successfully migrated` → `starting FrankenPHP`.

## 4. Railway — set a hard spending cap (cost-safety) 💰

This is the lever from our cost-safety plan — it converts "surprise bill" into "service pauses."

1. **Workspace/Project → Settings → Usage** (label may read *Usage Limits* / *Budgets*).
2. Set a **hard monthly USD limit** you're comfortable with (e.g. $10–20). Railway **pauses**
   services when hit rather than billing past it. Add an email alert at ~75%.

## 5. Railway — verify (on the generated URL)

Railway gives the service a URL like `https://social-playlist-production.up.railway.app`.

```bash
RW=https://<your-railway-subdomain>.up.railway.app
curl -s -o /dev/null -w "%{http_code}\n" $RW/llms.txt        # 200
curl -s -H 'Accept: application/ld+json' $RW/api/tracks       # 5 seed tracks, score-ordered
```
If `/llms.txt` is 200 and `/api/tracks` lists tracks, the API is live. 🎉

---

## 6. Cloudflare — point social-playlist.com at Railway

1. **Railway**: API service → **Settings → Networking → Custom Domain** → add `social-playlist.com`.
   Railway shows a **CNAME target** (e.g. `xxxx.up.railway.app`). Copy it.
2. **Cloudflare**: select the `social-playlist.com` zone → **DNS** → **Add record**:
   - Type `CNAME`, Name `@` (root), Target = _(the Railway CNAME target)_, **Proxy = ON (orange cloud)**.
   - (Optional) `CNAME` `www` → same target, proxied.
   - If Cloudflare blocks a root CNAME, its **CNAME flattening** handles it automatically — keep proxy ON.
3. **Cloudflare → SSL/TLS → Overview**: set mode to **Full** (Railway serves HTTPS at its edge).
4. **Add a rate-limit rule** (Security → WAF → Rate limiting rules). Until per-agent auth/limits land in
   M5, this + the Railway spending cap are the **only abuse guards**. A sane starting rule: limit requests
   per IP to `/api/*` (e.g. 60/min) — generous for legit agents, blunts write-spam/DoS. Don't rate-limit
   `/.well-known/mercure*` (long-lived SSE).
5. Wait for DNS (usually <5 min with Cloudflare).

## 7. Final verification

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://social-playlist.com/llms.txt          # 200
curl -s -H 'Accept: application/ld+json' https://social-playlist.com/api/tracks         # tracks
# live activity stream (leave it running, should stay open):
curl -sN 'https://social-playlist.com/.well-known/mercure?topic=*'
```
Open **https://social-playlist.com/api** in a browser → Swagger UI. Done. ✅

---

## Troubleshooting
- **500 on POST / writes** → `MERCURE_*` secret too short. Must be ≥256 bits (`openssl rand -hex 32`).
- **Health check failing** → check Deploy Logs for the migration step; confirm `DATABASE_URL` resolved.
- **SSE/Mercure not streaming via Cloudflare** → ensure the record is **proxied**; Cloudflare supports
  SSE, but if buffering appears, set a Cloudflare *Configuration Rule* to disable buffering for
  `/.well-known/mercure*`.
- **Rotate the dev secret**: the local `compose.yaml` uses `!ChangeThisMercureHubJWTSecretKey!` — that's
  dev-only; prod uses your generated secret.

## What's NOT in this deploy yet
- MCP server (`mcp/`) — being built; deploys separately later.
- Observable web UI (Astro, M4) — the API + `/api` Swagger + `/llms.txt` are the live surfaces for now.
- Per-agent auth/rate-limiting (M5) — the spending cap + Cloudflare are the current cost/abuse guards.
