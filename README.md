# Brightwork Consulting — static site

Static HTML replacement for the WordPress site at brightworkconsult.com, built
for deployment as its own Cloudflare Pages project. No build step — every page
is a single self-contained HTML file (inline CSS + vanilla JS), with Google
Fonts as the only external dependency.

## Status

Being rebuilt page by page, using `reference/wordpress-export/` (the actual
live WordPress/Elementor export) as the source of truth for content, images,
and effects — not the earlier draft layout files, which predated real
photography and the wave/star effects. Progress:

- [x] `index.html` — Home
- [x] `about.html` — About
- [ ] `services.html` — Services (new page; the old WordPress nav pointed at a
      file that was never provided, so this is being written from scratch
      using `reference/wordpress-export/our-services.html` as source copy)
- [ ] `contact.html` — Contact (uses Web3Forms for submissions — no server
      function needed)

## Brand

Palette matches the live WordPress site's CSS (navy `#08182A`, navy-mid
`#1A3550`, navy-dark `#0F2035`, brass `#C4973A`, gold `#D4A843`/`#C8A96E`,
sky-blue accent `#689AE2` used only in the hero). Fonts: Cormorant Garamond
(display) + Barlow (body), loaded from Google Fonts. Each page is clean
semantic HTML/CSS/JS — not a port of Elementor's markup, which is deeply
nested and depends on theme/plugin stylesheets that add no visual value once
rebuilt directly.

## Images

Real site photos and logos aren't checked into this repo yet — Claude's
sandbox can't reach brightworkconsult.com to download them. Run this once,
locally, on a machine with normal internet access:

```
./scripts/fetch-assets.sh
```

It downloads everything `index.html` currently references into
`assets/images/`. Until you run it, the hero slideshow and logos will show as
broken images.

## Corrections made vs. the live WordPress site

A few real bugs/inconsistencies turned up while porting Home and were fixed
rather than carried over — flagging them here in case they were intentional:

- **Phone number mismatch**: the page's JSON-LD schema listed
  `434-282-7215`, but the footer/CTA buttons show `434-825-9740`. Used
  `434-825-9740` everywhere (what visitors actually see and call) — confirm
  this is the right number.
- Two "Goal: ..." buttons in the Brightwork Split section had `href=""`
  (dead links) — rebuilt as non-clickable badges instead, matching what they
  visually read as.
- A CSS rule (`.value-card::after`) referenced color `c9a84c` without its
  leading `#` — invalid CSS, silently ignored by browsers. Fixed to a real
  gold value.
- The 5-step process grid was set to a fixed 3-column layout — same on the
  live site, which likely causes the last two steps to wrap awkwardly. Made
  it a proper 5-column layout instead.
- The hero slideshow image URLs included a stale `/staging/2433/` path
  segment not used by any other image on the site — stripped it to match the
  production upload path convention (see `scripts/fetch-assets.sh`).
- **About page**: Phase 2 ("The Design") and Phase 3 ("The Integration") of
  the process section shared a word-for-word duplicate bullet
  ("Meticulous Integration") in the original export — almost certainly a
  copy/paste mistake. Removed the duplicate from Phase 3 rather than
  inventing new copy, so Phase 3 currently has 2 supporting bullets instead
  of 3. If you want a third point there, it needs real copy from you (a
  third distinct thing that happens during "The Integration").
- **About page**: same `/staging/2433/`-path issue also appeared in the page's
  JSON-LD structured data and in an Elementor shape-divider asset URL — both
  fixed the same way as Home.
- Che Newton's headshot filename in the export
  (`28138_390197755495_538260495_4550325_5837766_n...`) looks like a raw old
  Facebook/CDN export name, unlike Rachel's professional-shoot filename
  (`A07A5207-copy-scaled-1...`). Worth confirming this is actually the
  current, intended photo before launch.

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
