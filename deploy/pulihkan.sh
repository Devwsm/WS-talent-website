#!/usr/bin/env bash
# Memulihkan kode versi sebelumnya di server dari cadangan satu versi (~/.deploy_prev) yang dibuat tiap deploy.
#
# Dipakai otomatis oleh workflow bila deploy gagal. Bisa juga dijalankan manual lewat SSH, mis. dari komputer lokal:
#   ssh -p PORT USER@HOST "APP_PATH='/home/USER/laravel' PUBLIC_PATH='/home/USER/public_html' bash -s" < deploy/pulihkan.sh
#
# Variabel:
#   APP_PATH, PUBLIC_PATH  wajib (sama seperti secret deploy).
#   DEPLOY_ID              opsional. Bila diisi, pemulihan hanya jalan jika cadangan dibuat oleh deploy yang sama, supaya
#                          deploy yang gagal sebelum menyentuh server tidak "memulihkan" cadangan milik deploy lama.
#                          Kosongkan untuk pemulihan manual (cadangan terakhir dipakai apa adanya).
#
# Yang dipulihkan: app, config, database, resources, routes, vendor, public/build, dan berkas lain yang tertimpa
# (artisan, composer.*, bootstrap/app.php, dst). Database TIDAK dikembalikan: migrasi yang sudah jalan tetap ada
# (migrasi di proyek ini bersifat menambah, jadi kode lama tetap bisa berjalan di atasnya).
# Cache hasil kode baru (bootstrap/cache, view terkompilasi) dibuang supaya tidak terpakai oleh kode lama.
set -uo pipefail

: "${APP_PATH:?APP_PATH wajib diisi}"
: "${PUBLIC_PATH:?PUBLIC_PATH wajib diisi}"

PREV="$HOME/.deploy_prev"
FOLDER="app config database resources routes vendor public/build"

angkat_pemeliharaan() {
  rm -f "$APP_PATH/storage/framework/down"
}

if [ ! -d "$PREV" ]; then
  echo "Tidak ada cadangan di $PREV, tidak ada yang dipulihkan."
  angkat_pemeliharaan
  exit 0
fi

if [ -n "${DEPLOY_ID:-}" ] && [ "$(cat "$PREV/.deploy_id" 2>/dev/null || true)" != "$DEPLOY_ID" ]; then
  echo "Cadangan bukan milik deploy ini (deploy gagal sebelum menyentuh kode di server). Kode tidak diubah."
  angkat_pemeliharaan
  exit 0
fi

gagal=0
for d in $FOLDER; do
  if [ -e "$PREV/$d" ]; then
    if rm -rf "$APP_PATH/$d" && mv "$PREV/$d" "$APP_PATH/$d"; then
      echo "Dipulihkan: $d"
    else
      echo "GAGAL memulihkan: $d"
      gagal=1
    fi
  fi
done

if [ -f "$PREV/lainnya.tgz" ]; then
  if tar -xzf "$PREV/lainnya.tgz" -C "$APP_PATH"; then
    echo "Dipulihkan: berkas lain (artisan, composer.*, bootstrap, dll)"
  else
    echo "GAGAL memulihkan berkas lain"
    gagal=1
  fi
fi

# Cache buatan kode baru tidak boleh dipakai kode lama (route/config ter-cache, view terkompilasi bertanggal lebih baru).
rm -f "$APP_PATH"/bootstrap/cache/*.php "$APP_PATH"/storage/framework/views/*.php

# public_html menyajikan salinan public/build.
if [ -d "$APP_PATH/public/build" ]; then
  rm -rf "$PUBLIC_PATH/build"
  cp -a "$APP_PATH/public/build" "$PUBLIC_PATH/build" || gagal=1
fi

# Cadangan sudah terpakai: tandai supaya tidak dipakai dua kali.
rm -f "$PREV/.deploy_id"

angkat_pemeliharaan

if [ "$gagal" -ne 0 ]; then
  echo "Pemulihan selesai dengan galat. Periksa server secara manual."
  exit 1
fi
echo "Pemulihan selesai. Situs kembali memakai kode versi sebelumnya."