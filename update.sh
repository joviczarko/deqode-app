#!/usr/bin/env bash
# Production update / emergency reinstall for DeQode (RunCloud).
#
# Usage (from app root on the server):
#   ./update.sh                 # pull + composer + npm + migrate + caches
#   ./update.sh --skip-pull     # same, but you already pulled / copied code
#   ./update.sh --bootstrap     # first install / rebuild on empty host (safe-ish)
#
set -euo pipefail

PIN="1205"
PHP="${PHP:-/RunCloud/Packages/php84rc/bin/php}"
COMPOSER="${COMPOSER:-/usr/sbin/composer}"
BRANCH="${BRANCH:-main}"

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

SKIP_PULL=0
BOOTSTRAP=0
for arg in "$@"; do
  case "$arg" in
    --skip-pull) SKIP_PULL=1 ;;
    --bootstrap|--install) BOOTSTRAP=1 ;;
    -h|--help)
      cat <<'EOF'
Usage: ./update.sh [--skip-pull] [--bootstrap]

  (default)     git pull → composer → npm build → migrate → caches → queue:restart
  --skip-pull   skip git pull (code already on disk)
  --bootstrap   first-time / emergency install extras:
                  storage:link, key:generate only if APP_KEY empty

PIN required. Never runs migrate:fresh or db:seed.
EOF
      exit 0
      ;;
    *)
      echo "Unknown option: $arg (try --help)"
      exit 1
      ;;
  esac
done

printf "PIN: "
read -r -s ENTERED_PIN
echo
if [[ "$ENTERED_PIN" != "$PIN" ]]; then
  echo "Wrong PIN. Aborting."
  exit 1
fi

if [[ "$BOOTSTRAP" -eq 1 ]]; then
  echo "==> BOOTSTRAP mode (first install / emergency rebuild)"
else
  echo "==> UPDATE mode"
fi
echo "==> App dir: $APP_DIR"

if [[ ! -f .env ]]; then
  echo "ERROR: .env missing. Copy from .env.example and fill production values first."
  exit 1
fi

if [[ "$SKIP_PULL" -eq 0 ]]; then
  echo "==> git pull origin $BRANCH"
  git pull origin "$BRANCH"
else
  echo "==> Skipping git pull (--skip-pull)"
fi

echo "==> composer install"
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

echo "==> npm ci && npm run build"
npm ci
npm run build

if [[ "$BOOTSTRAP" -eq 1 ]]; then
  echo "==> storage:link"
  "$PHP" artisan storage:link || true

  # Only generate a key when empty — never rotate an existing production key.
  if ! grep -qE '^APP_KEY=base64:.+' .env; then
    echo "==> APP_KEY empty → key:generate --force"
    "$PHP" artisan key:generate --force
  else
    echo "==> APP_KEY already set (leaving it alone)"
  fi
fi

echo "==> migrate --force"
"$PHP" artisan migrate --force

echo "==> cache config/routes/views"
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo "==> queue:restart"
"$PHP" artisan queue:restart

echo
echo "Done."
if [[ "$BOOTSTRAP" -eq 1 ]]; then
  cat <<'EOF'

Bootstrap checklist (one-time on this host):
  1. .env has APP_ENV=production, APP_DEBUG=false, DB_*, mail, S3, etc.
  2. Supervisor: queue worker (see ops docs when added)
  3. Cron: * * * * * php artisan schedule:run
EOF
fi
