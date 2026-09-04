#!/usr/bin/env sh
set -e

URL="${SMOKE_URL:-https://betsefer.appenlaweb.com/up}"

echo "Smoke test: ${URL}"
for i in 1 2 3 4 5; do
    if curl -fsS --max-time 15 "${URL}" >/dev/null 2>&1; then
        echo "OK (attempt ${i})"
        exit 0
    fi
    echo "not ready (attempt ${i}) — retrying in 5s"
    sleep 5
done

echo "Smoke test FAILED: ${URL} did not answer after 5 attempts." >&2
exit 1
