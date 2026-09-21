#!/usr/bin/env bash
# Run this once, locally, on a machine with normal internet access
# (Claude's sandbox can't reach brightworkconsult.com). It downloads the
# real site photos/logos referenced by the pages into assets/images/,
# under the local filenames the HTML already expects.
#
# Usage: ./scripts/fetch-assets.sh   (run from the repo root)

set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p assets/images

fetch() {
  local url="$1" dest="$2"
  if [ -s "$dest" ]; then
    echo "skip  $dest (already present)"
    return
  fi
  echo "fetch $dest"
  curl -fSL --retry 3 -o "$dest" "$url"
}

# Hero slideshow (the export's URLs included a stale /staging/2433/ path
# segment that no other image on the site uses -- stripped here to match
# the production upload path convention).
fetch "https://brightworkconsult.com/wp-content/uploads/2026/06/2026-hero10.webp"   assets/images/hero-1.webp
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-hero1-1.webp"  assets/images/hero-2.webp
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-hero17a.webp"  assets/images/hero-3.webp
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-hero12.webp"   assets/images/hero-4.webp
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-hero18.webp"   assets/images/hero-5.webp

# Logos
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-logo-full-light-1.png"   assets/images/logo-full-light.png
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-logo-full-dark-1.png"    assets/images/logo-full-dark.png
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-logo-full-light-1-r.png" assets/images/logo-full-light-retina.png
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-logo-stack-light-1-1.svg" assets/images/logo-stack-light.svg

# Badges / favicons
fetch "https://brightworkconsult.com/wp-content/uploads/2026/06/SWAM_LOGO-300x116.jpg" assets/images/swam-badge.jpg
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-logo-badge-dark-1-fill-85x85.png"  assets/images/favicon-32.png
fetch "https://brightworkconsult.com/wp-content/uploads/2026/05/2026-logo-badge-dark-1-fill-300x300.png" assets/images/favicon-192.png

# Open Graph share image
fetch "https://brightworkconsult.com/wp-content/uploads/2026/06/Screenshot-2026-06-02-101658.jpg" assets/images/og-image.jpg

echo
echo "Done. Review assets/images/, then: git add assets/images && git commit && git push"
