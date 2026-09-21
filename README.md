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
- [x] `services.html` — Services (rebuilt from
      `reference/wordpress-export/our-services.html`)
- [x] `contact.html` — Contact (Web3Forms backend — no server function
      needed). `contact-resend.html` is a second variant with a Cloudflare
      Pages Function + Resend backend instead — see "Contact form" below.

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
- **Services page**: the "Crew" section's background video
  (`magnific_create-a-video_UyVgRfGwny.mp4`) is now wired in as a real
  `<video autoplay muted loop>` behind the Gino Wickman quote panel (you
  confirmed it's a real, intended asset). **Please verify it actually plays
  on the live deployed site** — in this sandbox, neither of my two local
  test servers (Python's `http.server`, `wrangler pages dev`) sends the
  `Content-Length`/Range headers Chrome's `<video>` element wants, so I
  could only confirm the file itself downloads correctly and byte-matches,
  not that it renders. Cloudflare's real edge should serve static assets
  with proper headers, but I have no way to confirm that from here. If it
  doesn't play, the panel still looks fine — it falls back to a solid navy
  background.
- **Services page**: several section "headings" in the export (`The
  Shipyard`, `The Crew`, the AI-workflows heading, `Build a Seaworthy
  Legacy`) were actually plain `<p>` tags, not real heading elements — bad
  for SEO/accessibility. Rebuilt as real `<h2>`s.
- **Services page**: multiple info-box groups reused the exact same icon
  for every item in the group (all 4 "Shipyard" boxes, all 3 "AI Workflows"
  boxes), and the "Coming Soon" badge reused an unrelated people-icon from
  elsewhere on the page. Gave each item its own icon instead.
- **Services page**: one eyebrow label used a slightly different gold than
  every other eyebrow on the page (`#D4A843` vs. `#C8A96E`) — normalized to
  match.
- **Contact page**: same phone-number mismatch pattern as Home/About —
  JSON-LD schema said `434-282-7215`, visible buttons/footer say
  `434-825-9740`. Used the visible number consistently, as with the other
  pages.
- **Contact page**: all 4 "What Happens Next" trust bullets reused the
  exact same icon in the source. Gave each its own icon instead.
- **Contact page**: the closing-CTA paragraph had a stray, unmatched closing
  curly quote with no opening quote anywhere in the sentence (`We're experts
  at charting the course."` ) — read as broken/truncated copy. Removed the
  orphaned quote mark rather than guess what was originally meant to be
  quoted.

## Hero and footer background images

Home already had a real photo slideshow in its hero; About, Services, and
Contact originally shipped with flat navy hero backgrounds (no photo) since
none was captured during the initial page-by-page rebuild. All three now
have a real photo background (Ken Burns zoom, dark gradient overlay, grid
lines, animated waves — the same treatment as Home), using photos you
provided directly:

- About hero: `hero-2.webp`
- Services hero: `hero-3.webp`
- Contact hero: `2026-hero20-left.webp`

The `<footer>` on all four pages also now has a background image
(`2026-hero4.webp`, a dark aerial wave-wake photo) with a dark gradient
overlay tuned to keep the white footer text and links fully legible while
the texture still reads clearly.

## Deploying to Cloudflare Pages

This is a plain static site — no build command, no output directory other
than the repo root.

1. In the Cloudflare dashboard, create a new Pages project connected to this
   repo (or this branch).
2. Build command: none. Build output directory: `/` (repo root).
3. Deploy. Cloudflare Pages auto-detects `functions/api/contact.js` and
   deploys it as a Pages Function — no extra config needed for it to exist,
   though it won't actually send email until the environment variables in
   "Contact form" below are set. No environment variables are required for
   the rest of the site.

## Contact form: two variants

Both post the same 13-field assessment form (name, email, phone, business,
preferred contact method, best time, industry, revenue, employees,
challenges, timeline, notes, newsletter opt-in) to `contact@brightworkconsult.com`.
They're visually and functionally identical from a visitor's perspective —
only the backend differs.

**`contact.html` (linked from nav — the current default) — Web3Forms.**
Posts directly to Web3Forms (https://web3forms.com) from the browser — no
backend, no Cloudflare Function, no secrets to configure. The form keeps
working unchanged if the site is ever moved to a different Cloudflare
account or host entirely.

**Action needed**: the `access_key` currently embedded in `contact.html`
(`8efefabb-602d-4061-8cdc-356fcc99fc33`) is a leftover from an earlier
draft file, not something generated for this project — there's no
confirmed Web3Forms account behind it. To get a real one:

1. Go to https://web3forms.com and enter the email address you want
   assessment submissions delivered to (e.g. `contact@brightworkconsult.com`).
2. Web3Forms emails you a free Access Key — no account/password needed for
   the free tier.
3. Replace the `access_key` value in the hidden input near the top of the
   `<form id="assessment-form">` block in **both** `contact.html` (line
   ~421) — tell me the new key and I'll swap it in, or edit it directly
   yourself and push.

Until this is a real key, form submissions will fail silently (Web3Forms
will reject the unrecognized key).

**`contact-resend.html` (not linked from nav yet) — Cloudflare Pages
Function + Resend.** For when you're ready to move onto Cloudflare's own
native tools instead of a third-party form service. The form posts to
`/api/contact` (`functions/api/contact.js` in this repo), which sends the
email via Resend's API — entirely inside your Cloudflare account, no
third-party form host involved.

To activate it:
1. Sign up at resend.com and verify a sending domain (e.g. a subdomain of
   brightworkconsult.com) — Resend won't send from an unverified domain.
2. Create an API key at resend.com/api-keys.
3. In the Cloudflare Pages project's dashboard → Settings → Environment
   variables, add:
   - `RESEND_API_KEY` (as a **secret**) — the key from step 2
   - `CONTACT_FROM_EMAIL` (plain variable) — e.g.
     `Brightwork Website <notifications@brightworkconsult.com>`, using your
     verified domain
   - `CONTACT_TO_EMAIL` (plain variable, optional) — defaults to
     `contact@brightworkconsult.com` if omitted
4. Redeploy (environment variable changes require a new deployment to take
   effect).

Tested locally end-to-end with `npx wrangler pages dev .` (form validation,
honeypot, and the full Resend request path all verified working; the actual
email send needs a real Resend key to confirm, which I don't have).

**Switching the primary contact page later**: once you're happy with the
Resend variant, either swap the nav links across all four pages to point at
`/contact-resend.html` instead of `/contact.html`, or just replace
`contact.html`'s contents with `contact-resend.html`'s and delete the
duplicate — whichever you'd rather do.

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
