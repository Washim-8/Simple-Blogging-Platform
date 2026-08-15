# Uptime & Health Check Configuration

This document describes the health-check endpoints, Render configuration, and external uptime monitoring setup for the Simple Blog Platform.

---

## 1. Health-Check Endpoint

### Liveness Check: `/healthz`

This is a **lightweight liveness probe** that verifies the application process is running and responding. It does **not** depend on any external services (database, cache, etc.).

- **URL:** `GET /healthz`
- **Authentication:** None required
- **Response Time:** Immediate (< 10ms)
- **HTTP Status:** `200 OK`
- **Content-Type:** `application/json`
- **Response Body:**

```json
{
  "status": "ok"
}
```

### Readiness Check: `/readyz`

This is a **readiness probe** that verifies the application is ready to serve real traffic, including database connectivity.

- **URL:** `GET /readyz`
- **Authentication:** None required
- **HTTP Status:** `200 OK` when ready, `503 Service Unavailable` when not ready
- **Content-Type:** `application/json`
- **Response Body (healthy):**

```json
{
  "status": "ready",
  "checks": {
    "database": "ok"
  }
}
```

- **Response Body (using JSON fallback):**

```json
{
  "status": "ready",
  "checks": {
    "database": "json_fallback",
    "json_storage": "ok"
  }
}
```

### Liveness vs. Readiness

| Endpoint    | Purpose                              | Depends on DB? | Use For                      |
|-------------|--------------------------------------|----------------|------------------------------|
| `/healthz`  | Is the PHP/Apache process alive?     | **No**         | Render health check, Uptime monitors, Docker HEALTHCHECK |
| `/readyz`   | Is app ready to serve real requests? | **Yes**        | Load balancer traffic routing, post-deploy verification |

---

## 2. How to Test

### Local Testing

After starting the application locally (with Docker or PHP built-in server):

```bash
# Test liveness endpoint
curl -i http://localhost/healthz

# Expected output:
# HTTP/1.1 200 OK
# Content-Type: application/json
# {"status":"ok"}

# Test readiness endpoint
curl -i http://localhost/readyz

# Expected output when DB connected:
# HTTP/1.1 200 OK
# Content-Type: application/json
# {"status":"ready","checks":{"database":"ok"}}
```

### Browser Testing

Simply visit:
- `http://localhost/healthz`
- `http://localhost/readyz`

### Post-Deployment on Render

Replace `YOUR-RENDER-DOMAIN` with your actual Render subdomain:

```bash
curl -i https://YOUR-RENDER-DOMAIN.onrender.com/healthz
```

---

## 3. Render Configuration

### render.yaml (Infrastructure as Code)

The project includes `render.yaml` for one-click deployment. Key settings:

```yaml
services:
  - type: web
    runtime: docker
    healthCheckPath: /healthz    # Render uses this to verify your service is healthy
```

### Health Check Path Setting

When deploying manually via the Render Dashboard:

1. Go to **Your Web Service** → **Settings**
2. Scroll to **"Health Check Path"**
3. Set it to: `/healthz`
4. Click **Save Changes**

Render will periodically call this endpoint. If it returns a non-200 status multiple times, Render will mark the service as unhealthy and may restart it.

### Port Handling

- The Docker container uses Apache on port **80** (standard HTTP port)
- Apache is configured to bind to `0.0.0.0:80` by default (not localhost-only)
- Render automatically maps its external HTTPS port to the container's port 80
- No `PORT` environment variable handling is required for PHP/Apache Docker deployments on Render

---

## 4. Internal Keep-Alive: Render Cron Job (No External Sites)

If you **do not want to use any external monitoring websites** (UptimeRobot, etc.), you can use Render's **native Cron Job feature** — it runs entirely inside Render's infrastructure, no third-party sites needed.

### How It Works

```
Render Cron Scheduler (every 5 minutes)
        │
        ▼
Runs: curl https://your-project.onrender.com/healthz
        │
        ▼
Your web service wakes up / stays warm
```

This is **Render's own feature**, not a hack. The cron job is defined in `render.yaml` alongside your web service:

```yaml
services:
  - type: web
    name: simple-blog-platform
    # ... (your web service config) ...

  - type: cron                        # ← Render's native cron job
    name: blog-keep-awake
    runtime: docker
    schedule: "*/5 * * * *"          # Every 5 minutes
    command: 'curl -fsS "$PING_URL/healthz" || echo "Ping failed"'
    envVars:
      - key: PING_URL
        value: https://YOUR-RENDER-DOMAIN.onrender.com
```

### Setup Instructions

1. **Push `render.yaml` changes to GitHub** (already done if you followed this guide)
2. **Go to Render Dashboard → "Blueprints"**
3. If you already deployed via Blueprint:
   - Render will detect the `render.yaml` change and auto-add the cron job
   - Or click **"Manual Deploy" → "Deploy latest commit"**
4. If not using Blueprint mode:
   - Go to **"New +" → "Cron Job"**
   - Connect the same repo (`Washim-8/Simple-Blogging-Platform`)
   - Configure:
     ```
     Name: blog-keep-awake
     Branch: main
     Runtime: Docker
     Dockerfile Path: ./Dockerfile
     Schedule: */5 * * * *
     Command: curl -fsS "$PING_URL/healthz" || echo "Ping failed"
     Environment Variable PING_URL = https://YOUR-RENDER-DOMAIN.onrender.com
     ```
5. **IMPORTANT:** Replace `YOUR-RENDER-DOMAIN` with your actual subdomain (e.g. `simple-blog-platform.onrender.com`)

### Set PING_URL After First Deploy

After your web service is live and you know its actual domain:

1. Go to **Render Dashboard → Cron Jobs → blog-keep-awake → Environment**
2. Edit the `PING_URL` variable:
   ```
   Key:   PING_URL
   Value: https://simple-blog-platform.onrender.com   ← put your REAL domain here
   ```
3. Save changes → cron job redeploys automatically

### Cron Job Free-Tier Quota

Render free cron jobs are billed like web services (minutes count toward your 750h/month cap). A 5-minute curl ping takes ~1 second, so monthly usage is:

```
12 pings/hour × 24 hours × 30 days × ~0.02 min/ping ≈ 17 minutes/month
```

**≈ 17 minutes out of 750 hours (45,000 minutes)** — negligible impact.

---

## 5. External Uptime Monitoring (Optional Alternatives)

#### Option A: UptimeRobot (Recommended, Free Tier)

1. Sign up at [uptimerobot.com](https://uptimerobot.com/)
2. Click **"Add New Monitor"**
3. Configure:
   - **Monitor Type:** HTTP(s)
   - **Friendly Name:** `Simple Blog Platform - Health`
   - **URL (or IP):** `https://YOUR-RENDER-DOMAIN.onrender.com/healthz`
   - **Monitoring Interval:** Every **5 minutes** (minimum on free tier)
4. Click **Create Monitor**

#### Option B: Better Uptime

1. Sign up at [betterstack.com/better-uptime](https://betterstack.com/better-uptime)
2. Create a new **HTTP Monitor**
3. Set URL to: `https://YOUR-RENDER-DOMAIN.onrender.com/healthz`
4. Set check interval: **3-5 minutes**
5. Configure email/SMS alerts for downtime

#### Option C: Cron-job.org

1. Sign up at [cron-job.org](https://cron-job.org/)
2. Click **"Create cronjob"**
3. Configure:
   - **Title:** Blog Health Check
   - **URL:** `https://YOUR-RENDER-DOMAIN.onrender.com/healthz`
   - **Schedule:** Every 5 minutes (`*/5 * * * *`)
4. Save and enable

### What the Monitor URL Looks Like

```
https://your-project.onrender.com/healthz
           │                          │
           │                          └── Always use /healthz
           └── Replace with your actual Render domain
```

### Alert Notifications

Configure the monitoring service to notify you via:
- Email
- SMS
- Slack/Discord webhook
- Mobile push notification

When the endpoint returns a non-200 status or times out, you'll receive an alert.

---

## 5. Free-Tier Limitations (Critical)

### IMPORTANT: No Guaranteed 24×7 Uptime on Free Tier

A health-check endpoint + external monitoring **does NOT guarantee permanent 24×7 uptime** on Render's Free Tier. Here's why:

| Factor | Free Tier Behavior |
|--------|-------------------|
| **Idle Spin-Down** | Service stops after ~15 min of no external traffic |
| **Monitoring Ping** | Periodic pings can **reduce** idle time but **cannot guarantee** the service never sleeps |
| **Cold Start** | First request after spin-down takes 10-60 seconds |
| **Monthly Limits** | 750 hours of runtime per month (shared across all free services) |
| **Render Policy** | Render may enforce sleep periods regardless of incoming requests |

### What External Monitoring Actually Does

✅ Helps keep the service warm and responsive
✅ Alerts you when the service is actually down/unhealthy
✅ Reduces average idle periods
✅ Provides historical uptime statistics

❌ Does **not** override Render's free-tier sleep policy
❌ Does **not** bypass the 750-hour monthly limit
❌ Does **not** eliminate cold starts entirely

---

## 6. Paid Always-On Option

For production use cases requiring guaranteed availability, upgrade to a Render **Starter** (or higher) plan:

### Benefits of Paid Plans:

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| Always-on (no spin-down) | ❌ | ✅ | ✅ |
| Cold starts | Frequent | None | None |
| 750h monthly limit | ✅ | ❌ | ❌ |
| RAM | 512 MB | 2 GB | 4 GB |
| Custom domains | ❌ | ✅ | ✅ |
| SSH access | ❌ | ✅ | ✅ |

### How to Upgrade

1. Go to [Render Dashboard](https://dashboard.render.com/)
2. Select your Web Service
3. Click **"Upgrade Plan"**
4. Choose **Starter** ($7/month as of 2026) or higher
5. The service will restart and remain always-on

With a paid plan:
- You can **disable** external uptime monitors (they're no longer needed for wake-ups)
- Keep monitoring enabled **only** for alerting on actual outages
- Your `/healthz` endpoint will respond instantly at all times

---

## 7. Security Considerations

### Safe Design (Already Implemented)

✅ `/healthz` does **not** expose:
- Database credentials
- Environment variables
- API keys
- Server internals
- Stack traces
- File paths
- Version numbers (unless explicitly added)

✅ Response is minimal, static JSON

✅ No authentication required (intentionally — monitors need anonymous access)

✅ No database queries — only basic process health

### Recommendations

1. **Do NOT add sensitive data** to either health endpoint response
2. **Do NOT require authentication** on `/healthz` or `/readyz` — monitors are anonymous
3. **Use HTTPS always** — Render provides this automatically for `.onrender.com` domains
4. **Rate limiting** is usually not needed — these endpoints consume almost zero resources
5. If you add custom fields, stick to: `status`, `timestamp`, `version` (non-sensitive)

---

## 8. Docker HEALTHCHECK

The `Dockerfile` includes an internal container health check:

```dockerfile
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/healthz || exit 1
```

This is **separate** from:
- Render's platform health check (`healthCheckPath: /healthz`)
- Your external uptime monitor

Each layer serves a different purpose:

| Health Check Layer | Where It Runs | Purpose |
|--------------------|---------------|---------|
| Docker HEALTHCHECK | Inside the container | Docker restart policies, local `docker ps` status |
| Render Health Check | Render platform (external to container) | Mark service as healthy/unhealthy, trigger redeploys |
| External Uptime Monitor | Third-party service (fully external) | Cross-region availability monitoring, user alerts |

---

## Summary Checklist

Before going live, verify all of the following:

- [ ] `/healthz` returns `HTTP 200` → `{"status":"ok"}`
- [ ] `/readyz` returns `HTTP 200` when DB is connected
- [ ] Render **Health Check Path** is set to `/healthz` (in Dashboard or render.yaml)
- [ ] External uptime monitor configured with `/healthz` URL
- [ ] Alert notifications working (email/SMS/Slack)
- [ ] Understanding that Free Tier ≠ guaranteed uptime
- [ ] Considered Starter plan for production always-on
- [ ] No sensitive data exposed in health responses
