#!/usr/bin/env bash
set -euo pipefail

# Siapkan paket SIRESTEDUH siap upload/deploy (Laravel).
# - Build aset frontend (Vite)
# - Install dependensi PHP produksi + optimize autoloader
# - Bersihkan cache lokal/dev
# - Buat arsip .zip siap upload (vendor + public/build disertakan)
#
# Pemakaian:
#   bash scripts/prepare-deploy.sh [--skip-build] [--skip-composer] [--skip-zip]
#
# Catatan:
# - Menjalankan composer --no-dev: dependensi dev (phpunit/pint/dll) akan terhapus dari vendor.
#   Untuk lanjut development setelahnya: composer install
# - .env dan database sqlite lokal TIDAK disertakan dalam zip (atur di server).

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SKIP_BUILD=0
SKIP_COMPOSER=0
SKIP_ZIP=0
for arg in "$@"; do
  case "$arg" in
    --skip-build) SKIP_BUILD=1 ;;
    --skip-composer) SKIP_COMPOSER=1 ;;
    --skip-zip) SKIP_ZIP=1 ;;
    -h|--help)
      sed -n '1,40p' "$0"; exit 0 ;;
    *) echo "Argumen tidak dikenal: $arg" >&2; exit 1 ;;
  esac
done

need() { command -v "$1" >/dev/null 2>&1 || { echo "Perintah tidak ditemukan: $1" >&2; exit 1; }; }
need php
need composer

echo "==> [1/5] Cek environment"
php -v | head -n 1
composer --version | head -n 1

if [ "$SKIP_COMPOSER" -eq 0 ]; then
  echo "==> [2/5] Install dependensi PHP (production, optimized)"
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
  echo "==> [2/5] Lewati composer (--skip-composer)"
fi

if [ "$SKIP_BUILD" -eq 0 ]; then
  echo "==> [3/5] Build aset frontend"
  need npm
  if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
  else
    npm install --no-audit --no-fund
  fi
  npm run build
else
  echo "==> [3/5] Lewati build aset (--skip-build)"
fi

echo "==> [4/5] Bersihkan & siapkan cache aplikasi"
php artisan optimize:clear --no-interaction || true
php artisan package:discover --ansi --no-interaction || true
# Buat storage link bila belum ada (abaikan jika gagal di lingkungan build)
php artisan storage:link --no-interaction >/dev/null 2>&1 || true

if [ "$SKIP_ZIP" -eq 1 ]; then
  echo "==> [5/5] Lewati pembuatan zip (--skip-zip)"
  echo "Selesai (tanpa arsip)."
  exit 0
fi

echo "==> [5/5] Buat arsip siap upload"
need zip
mkdir -p deploy
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="deploy/siresteduh-deploy-${STAMP}.zip"
rm -f "$OUT"

# Sertakan file app + vendor + public/build; kecualikan dev/env/data lokal.
zip -rq "$OUT" . \
  -x ".git/*" \
  -x ".github/*" \
  -x "node_modules/*" \
  -x "tests/*" \
  -x "deploy/*" \
  -x ".env" \
  -x ".env.*" \
  -x "!.env.example" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/data/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x "database/*.sqlite" \
  -x "database/*.sqlite-*" \
  -x "*.zip" \
  -x ".phpunit.result.cache" \
  -x ".DS_Store"

# Pastikan .env.example tetap ada di arsip (jika ter-exclude oleh pola .env.*)
if [ -f .env.example ]; then
  zip -q "$OUT" .env.example || true
fi

echo "Paket siap upload: $OUT"
echo "Ukuran: $(du -h "$OUT" | cut -f1)"
echo
echo "Langkah di server setelah upload & extract:"
echo "  1) cp .env.example .env  (atau upload .env) lalu set APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, dll"
echo "  2) php artisan key:generate --force   (jika APP_KEY kosong)"
echo "  3) php artisan migrate --force"
echo "  4) php artisan storage:link"
echo "  5) php artisan config:cache && php artisan route:cache && php artisan view:cache"
echo "  6) Pastikan web root ke /public dan permission storage/ bootstrap/cache writable"
