#!/bin/bash
set -e

# ==============================================================================
# Render Free-Tier Keep-Alive Background Daemon
# ==============================================================================
(
  # Wait for Apache to fully start and listen
  sleep 25
  
  # Resolve target URL: prefer RENDER_EXTERNAL_URL injected by Render, fallback to PING_URL or default domain
  TARGET_URL="${RENDER_EXTERNAL_URL:-${PING_URL:-https://simple-blogging-platform-6vqg.onrender.com}}"
  TARGET_URL="${TARGET_URL%/}"
  
  if [ -n "$TARGET_URL" ]; then
    echo "[KeepAlive Daemon] Started self-pinging $TARGET_URL every 8 minutes"
    while true; do
      # Sleep for 8 minutes (480 seconds) before next ping to prevent 15-min spin-down
      sleep 480
      echo "[KeepAlive Daemon] Sending ping to $TARGET_URL/healthz at $(date)"
      curl -fsS --max-time 15 "$TARGET_URL/healthz" >/dev/null 2>&1 || echo "[KeepAlive Daemon] Ping failed or timed out at $(date)"
    done
  else
    echo "[KeepAlive Daemon] No external URL configured; background daemon inactive."
  fi
) &

# ==============================================================================
# Start Apache in the foreground
# ==============================================================================
exec apache2-foreground
