#!/usr/bin/env bash
# A memo téma assetjeit átmásolja a moni2026-ba. A projekt gyökeréből futtasd:
#   bash web/themes/custom/moni2026/install-assets.sh
set -euo pipefail
SRC=web/themes/custom/memo
DST=web/themes/custom/moni2026
cp "$SRC/favicon.ico" "$SRC/logo.svg" "$SRC/screenshot.png" "$DST/"
cp "$SRC/js/imagesLoaded.min.js" "$DST/js/"
# ck5style.css: saját, Bootstrap nélküli változat a témában.
sed -e 's/b4_body_schema/b5_body_schema/g' -e 's/b4_body_bg_schema/b5_body_bg_schema/g' \
  "$SRC/templates/html.html.twig" > "$DST/templates/layout/html.html.twig"
echo "moni2026 assets OK (téma nincs aktiválva)."
