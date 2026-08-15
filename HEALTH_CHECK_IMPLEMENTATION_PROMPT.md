# Reusable Implementation Prompt: Render Health-Check + Internal Keep-Alive (No External Sites)

> **USAGE:** Copy this entire block as a prompt into any AI editor (Trae, Cursor, Copilot, etc.) for ANY server-side project you want to deploy on Render Free Tier without using external uptime monitors.
>
> **Author:** Implementation pattern for PHP/Node/Python/Docker on Render

---

## MASTER PROMPT — Paste This Into the AI

```
You are a DevOps engineer specializing in Render deployments, Docker, and production-safe server architecture.

Your task is to add a HEALTH-CHECK SYSTEM + INTERNAL KEEP-ALIVE to this project for deployment on Render. NO external uptime monitoring sites (no UptimeRobot, no Better Uptime, no cron-job.org). Use ONLY Render's native features.

==================================================
PHASE 1 — ANALYZE THE PROJECT FIRST
==================================================

Inspect the entire project. Identify:
- Programming language / framework (PHP, Node/Express, Python/Flask/FastAPI/Django, etc.)
- Web server (Apache, Nginx, built-in, Docker base image)
- Entry point
- Existing routes, API routes
- Dockerfile (if exists)
- render.yaml (if exists)
- Any existing health endpoint (do NOT create duplicates — improve existing)

DO NOT modify any code until you confirm the stack.

==================================================
PHASE 2 — CREATE HEALTH ENDPOINTS
==================================================

Create TWO endpoints (use framework-native syntax):

---
ENDPOINT A:  GET /healthz   (LIVENESS PROBE)
---

REQUIREMENTS:
- HTTP 200 always
- Content-Type: application/json
- Response body: {"status":"ok"}
- NO authentication required
- NO database queries, NO external API calls, NO Redis checks
- Must return 200 even if the database is dead
- Must not trigger any side effects, background jobs, or writes
- Cache-Control: no-cache, no-store, must-revalidate
- Response time < 10ms (minimal processing)

Purpose: "Is the application process alive and responding?"

---
ENDPOINT B:  GET /readyz   (READINESS PROBE — optional but recommended)
---

REQUIREMENTS (only if project has DB/external deps):
- HTTP 200 when ready, HTTP 503 when not ready
- Content-Type: application/json
- Validate database connectivity (simple SELECT 1) OR critical dependency check
- For projects with fallbacks (e.g., JSON file storage), include fallback status in response
- No auth required
- No writes/side effects
- Response body example:
  {"status":"ready","checks":{"database":"ok"}}
  {"status":"ready","checks":{"database":"json_fallback","json_storage":"ok"}}

Purpose: "Is the application ready to serve REAL user traffic successfully?"

Separate file/module:
- Create dedicated files: healthz.ext and readyz.ext (where ext = php, py, js, ts)
- OR place in existing routes/health module (follow project's existing structure)

==================================================
PHASE 3 — ENSURE CLEAN URL ROUTING
==================================================

The endpoints must be reachable WITHOUT file extension:
   /healthz   (not /healthz.php, not /api/healthz.js)

Fix routing:
- PHP/Apache (.htaccess):
    RewriteRule ^healthz$ healthz.php [L]
    RewriteRule ^readyz$ readyz.php [L]
- Node/Express: app.get('/healthz', ...) (already clean)
- FastAPI/Flask: @app.get("/healthz") (already clean)
- Django: add path('healthz/', views.healthz, ...) in urls.py

==================================================
PHASE 4 — DOCKER HEALTHCHECK (IF DOCKER USED)
==================================================

Update Dockerfile:
1. Ensure `curl` is installed in the image (apt-get install curl, apk add curl, etc.)
2. Add Docker HEALTHCHECK instruction:
    HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
        CMD curl -f http://localhost/healthz || exit 1
3. Do NOT hardcode localhost-only binding. Web server must listen on 0.0.0.0:<port>
4. Respect PORT env var if applicable (process.env.PORT in Node, $PORT in Python)
   - Apache/Nginx Docker: port 80 is fine (Render maps externally automatically)

==================================================
PHASE 5 — RENDER CONFIGURATION + INTERNAL CRON KEEP-ALIVE
==================================================

Create/update render.yaml in project root with:

```yaml
services:
  - type: web
    name: <APP-NAME>
    runtime: docker          # OR native: python, node, etc.
    # ... existing web service config ...
    healthCheckPath: /healthz     # ← CRITICAL: Render platform-level check
    envVars:
      # ... (DB credentials, etc.) ...

  # ================================================
  # INTERNAL KEEP-ALIVE (NO EXTERNAL SITES)
  # Uses Render's OWN Cron Job scheduler
  # ================================================
  - type: cron
    name: <APP-NAME>-keep-awake
    runtime: docker          # OR match web service runtime
    repo: <YOUR-GITHUB-REPO-URL>   # Same repo as web service
    branch: main
    dockerfilePath: ./Dockerfile   # Or omit if using native runtime
    schedule: "*/5 * * * *"        # Every 5 minutes (do NOT go lower — wastes quota)
    command: 'curl -fsS "$PING_URL/healthz" || echo "Ping failed (service cold-starting)"'
    envVars:
      - key: PING_URL
        value: https://PLACEHOLDER-REPLACE-WITH-ACTUAL-DOMAIN.onrender.com

# ... (database definitions, etc.) ...
```

IMPORTANT NOTES FOR render.yaml:
- Placeholder PING_URL MUST be replaced by the USER after first deploy
- Cron schedule: `*/5 * * * *` = every 5 minutes. Do NOT use `*/1` — excessive.
- The cron job is the SAME repo as the web service (reuses code/image)
- Runtime for cron should MATCH web service runtime

==================================================
PHASE 6 — CREATE UPTIME.md DOCUMENTATION
==================================================

Create UPTIME.md with these sections:

1. HEALTH ENDPOINTS:
   - /healthz (liveness): method, auth, status 200, response {"status":"ok"}, NO deps
   - /readyz (readiness): method, auth, 200/503, includes DB check
   - Table comparing liveness vs readiness

2. HOW TO TEST LOCALLY:
   - curl commands, expected output

3. RENDER CONFIG:
   - healthCheckPath = /healthz
   - Port handling (0.0.0.0, PORT env, Apache/80)

4. INTERNAL KEEP-ALIVE (Render Cron, NO external sites):
   - Diagram: Render scheduler → curl /healthz → service stays warm
   - render.yaml snippet
   - Step-by-step: after-deploy setup IN RENDER DASHBOARD
     * Step 1: Sync render.yaml / Create Cron Job manually in Render → New → Cron Job
     * Step 2: SET PING_URL env var in Dashboard to REAL domain (critical!)
   - Quota cost: ~17 minutes/month out of 750h (negligible)

5. FREE-TIER LIMITATIONS (HONEST, NON-NEGOTIABLE):
   Table:
   ✅ What cron DOES: reduce idle periods, wake service on schedule, 0.04% quota cost
   ❌ What cron CANNOT: override Render forced sleep, bypass 750h cap, guarantee 24x7
   - Paid Starter plan = ONLY way for guaranteed always-on ($7/mo as of 2026)

6. SECURITY:
   - Endpoints expose NO secrets, creds, env vars, stack traces
   - Responses minimal, static JSON

==================================================
PHASE 7 — CRITICAL RULES — NEVER VIOLATE THESE
==================================================

DO NOT, UNDER ANY CIRCUMSTANCES, ADD THESE:
❌ No JavaScript setInterval(fetch('/healthz')) — runs only when user has tab open
❌ No PHP/Python/Node infinite loops (while(true)) — web servers kill long requests
❌ No background self-ping processes, no daemon scripts
❌ No aggressive pinging (< 5 min interval) — wastes quota
❌ No fake traffic generation patterns / anti-spin-down hacks
❌ No recursive requests or multi-process ping schemes
❌ Do NOT claim "guaranteed 24x7 uptime on free tier" — LIE. Be honest.

Reason for rules: PHP/Node/FastCGI processes are request-driven and die after each request. Between requests, NO code is running. Render suspends the entire container when idle. Internal pings CANNOT prevent platform-level container suspension. ONLY Render's external cron scheduler can issue real wake-up calls.

==================================================
PHASE 8 — FINAL DELIVERABLES
==================================================

When done, provide:

✅ Files created list (healthz.ext, readyz.ext, render.yaml, UPTIME.md)
✅ Files modified list (.htaccess, Dockerfile, existing routes)
✅ Syntax check all modified files
✅ Run the project locally and PROVE:
   - GET /healthz → HTTP 200 + {"status":"ok"}
   - GET /readyz → HTTP 200/503 as appropriate
   - Homepage still works
   - All existing APIs still work
✅ Git commit message: "Add Render health-check system + internal cron keep-alive (no external sites)"

==================================================
FINAL REMINDER
==================================================

You are building a LEGITIMATE, production-safe system using Render's documented features (health checks + cron jobs), not hacks or workarounds. The system should be clean, reusable across projects, and honest about limitations.
```

---

## Quick Reference Cheat Sheet

| Component | PHP/Apache Example | Node/Express Example |
|-----------|--------------------|---------------------|
| `/healthz` file | `healthz.php` with json_encode + exit | route: `app.get('/healthz', (r,res)=>res.json({status:'ok'}))` |
| Clean URL | `.htaccess` RewriteRule | Already clean |
| Docker curl | `apt-get install -y curl` | `apt-get install -y curl` (Debian) or `apk add curl` (Alpine) |
| Docker HEALTHCHECK | Same: `curl -f http://localhost/healthz` | Same: `curl -f http://localhost:$PORT/healthz` |
| render.yaml cron command | `curl -fsS "$PING_URL/healthz" \|\| true` | Same |

---

## For Native Runtimes (Non-Docker) on Render

If the project uses Render native runtime (not Docker) — e.g., `runtime: node`, `runtime: python`:

1. Omit `dockerfilePath`
2. Keep the cron runtime MATCHING the web runtime
3. Cron command for Node native:
   ```yaml
   command: node -e "require('https').get(process.env.PING_URL+'/healthz', r=>console.log('status:',r.statusCode)).on('error',e=>console.log('fail:',e.message))"
   ```
4. Cron command for Python native:
   ```yaml
   command: python -c "import urllib.request,os; urllib.request.urlopen(os.environ['PING_URL']+'/healthz')"
   ```
5. No curl install needed for native (use language stdlib HTTP client)
