# Brightwork Consulting — static site

Static HTML replacement for the WordPress site at brightworkconsult.com, built
for deployment as its own Cloudflare Pages project. No build step — every page
is a single self-contained HTML file (inline CSS + vanilla JS), with Google
Fonts as the only external dependency.

## Status

Being rebuilt page by page. Progress:

- [x] `index.html` — Home
- [ ] `about.html` — About
- [ ] `services.html` — Services (new page; the old WordPress nav pointed at a
      file that was never provided, so this is being written from scratch
      using `reference/wordpress-export/our-services.html` as source copy)
- [ ] `contact.html` — Contact (uses Web3Forms for submissions — no server
      function needed)

## Brand

Palette matches the live WordPress site's CSS (navy `#08182A`, brass
`#C4973A`/`#B8922A`, cream `#F3F0E7`), not the teal/blue draft palette the
source files originally shipped with. Fonts: Cormorant Garamond (display) +
Barlow (body), loaded from Google Fonts.

## Deploying to Cloudflare Pages

This is a plain static site — no build command, no output directory other
than the repo root.

1. In the Cloudflare dashboard, create a new Pages project connected to this
   repo (or this branch).
2. Build command: none. Build output directory: `/` (repo root).
3. Deploy. No environment variables or secrets are required.

## Contact form

`contact.html` posts directly to Web3Forms (https://web3forms.com) from the
browser — no backend, no Cloudflare Function, no secrets to configure. This
also means the form keeps working unchanged if the site is ever moved to a
different Cloudflare account or host.

**Before going live**, confirm the `access_key` hidden input in the form
belongs to *your* Web3Forms account (not a leftover from a prior draft) —
generate a fresh key at web3forms.com/profile if you're unsure, and swap it
in.

## Before this goes fully live

- Every page currently ships `<meta name="robots" content="noindex, nofollow">`
  and `<link rel="canonical" href="https://brightworkconsult.com/...">` —
  intentional while this coexists with the live WordPress site. Remove the
  `noindex, nofollow` meta tag (or change it to `index, follow`) once this
  site is the one actually serving brightworkconsult.com.

## Reference material

`reference/wordpress-export/` holds the original WordPress/Elementor page
exports (not deployed — kept only as source copy for content still being
ported, e.g. the Services page).
