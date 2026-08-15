# Full site audit — `ilanel.dads42.com` — 2026-08-15

Independent audit of the live Cloudways build, run from a real browser
against the served site (not the Playground demo, not `ilanel.com`).

**Scope:** 163 URLs from `wp-sitemap.xml` plus link-discovered pagination
(175 unique URLs total), 163 legacy `ilanel.com` URLs tested for migration
parity, axe-core WCAG 2.1 AA passes on five templates, rendered-DOM and
computed-CSS comparison against `rossgardam.com.au` and `marzdesigns.com`.

**Reading this alongside the handover:** `docs/HANDOVER-2026-08-15.md` is
accurate on everything I could check — AUD currency is live in the product
schema, static pages are real, the $3,450 placeholder is deliberate and
documented. This audit is not a correction of it; it covers ground the
handover doesn't: SEO metadata, migration URL parity, contrast, and the
conversion path.

**Excluded by instruction:** prices and imagery are not final, so
placeholder pricing and photo selection are not counted as defects. Where
a *mechanism* around them is broken (identical titles on distinct
products, no image dimensions), that is in scope.

---

## Verdict

The build is structurally sound and visually far closer to the reference
than anything before it. What it is not yet is **commercially connected**
or **migration-safe**. Three things stand out:

1. **There is no way for a customer to transact or enquire.** "Buy now",
   "Send enquiry" and "Specifier enquiry" all link to `/contact/`, and
   `/contact/` has no form — only a `mailto:`. The entire funnel, B2C and
   B2B, terminates on a page with no capture mechanism.
2. **~63 of ~213 live `ilanel.com` URLs currently 404 here**, including
   `/lighting-design-collections` and `/editions` — the two collection
   index URLs. With 1,110 of ~1,130 backlinks pointing at this domain,
   cutover without a redirect map is the single largest risk on the board.
3. **Zero meta descriptions, zero `og:image`, zero Twitter cards across
   all 162 indexable pages.** Every share of every URL — to a client, on
   Instagram, in an email — renders as a bare link.

None of these are hard engineering. All three are cheap now and expensive
after Phase 5.

| Area | State |
|---|---|
| Information architecture | Good — matches live product URLs exactly |
| Product template | Strong — richest page on the site |
| Migration URL parity | **Incomplete — ~63 legacy URLs 404** |
| Conversion path | **Broken — no form anywhere on the site** |
| SEO metadata | **Absent — 162/162 pages** |
| Structured data | Partial — products good, everything else thin |
| Accessibility (automated) | Very good — 1 violation across 5 templates |
| Accessibility (contrast) | **Fails on light hero imagery** |
| Performance | Good — 33 requests, ~704 KB, 2.1 s load |
| Visual parity vs Ross Gardam | Close on layout, diverges on type and chrome |

---

## P0 — blocks launch

### 1. No enquiry or newsletter capture exists anywhere on the site

Verified on `/`, `/trade/`, `/contact/`, `/faq/` and product pages: the
only `<form>` on the entire site is the product configurator
(`.rg-config`, on 34 product pages), the WooCommerce search on `/shop/`,
and `/my-account/`. There is **no contact form, no enquiry form, no
specifier form, and no newsletter signup**.

Consequences, in order of cost:

- **`/contact/` has no form.** It carries the showroom address, a phone
  number and three headed sections — *Press Enquiries*, *Specifier
  Enquiries*, *Representation Enquiries* — each describing who should get
  in touch, and then offering no mechanism to do so beyond one
  `mailto:studio@ilanel.com`.
- **All three product CTAs point at that page.** On
  `/lighting-design-collections/comet-pendant-light/`, "Buy now", "Send
  enquiry" and "Specifier enquiry" are three visually distinct buttons
  with the identical `href="https://ilanel.dads42.com/contact/"`. The
  configured length, colourway and price the customer just chose are not
  carried through. Three CTAs, one destination, zero context.
- **"Buy now" does not buy.** Products are purchasable in WooCommerce per
  the handover, but the product template's primary CTA bypasses the cart
  entirely.
- **The newsletter — ~4,000 subscribers, described in `ilanel-studio` as a
  core owned channel — has no signup on the site.** The footer "Subscribe"
  is `href="#"`.

Ross Gardam's product page carries four forms (search, newsletter,
enquiry, variations). Marz carries five. ILANEL carries one, and it
doesn't submit anywhere.

**Fix:** one enquiry form on `/contact/` with a hidden product/config
field, a separate trade-application form on `/trade/`, and a newsletter
capture in the footer. Point "Buy now" at `?add-to-cart=`.

### 2. ~63 legacy `ilanel.com` URLs return 404

163 of the 213 URLs in `ilanel-studio/reports/live-urls-2026-08-10.txt`
were tested directly against this site. **18 unique URLs 404.** By
sampling, the untested remainder (21 `/news/category/*`, 21
`/news/tag/*`, 5 `/portfolio-1-1/*`) also 404 — both sampled members of
each group returned 404 — bringing the projected total to **~63 of ~213,
about 30% of the live URL surface.**

Confirmed 404s:

| Legacy URL | Note |
|---|---|
| `/lighting-design-collections` | **Collection index — the main range URL** |
| `/editions` | **Editions index** |
| `/lighting-design-collections/finishes` | Finishes reference page |
| `/lighting-design-collections/snowball/walllight` | Live uses a nested path; build uses `snowball-walllight` |
| `/3d-models` | Real page on live (request form) |
| `/services` | |
| `/news/category/*` (21) | Sampled `Art`, `Bespoke` — both 404 |
| `/news/tag/*` (21) | Sampled `lighting`, `Brass` — both 404 |
| `/portfolio-1-1` + 6 children | Legacy Squarespace portfolio |
| `/linktree`, `/members-photos`, `/ip-address` | Utility pages |
| `/join-our-team-craftsman`, `/join-our-team-id` | Recruitment |
| `/customer-satisfaction-survey`, `/coming-soon`, `/404-page` | Low value |

Only two legacy URLs redirect correctly today: `/home → /` and
`/privacy → /privacy-policy/`.

The `/lighting-design-collections` and `/editions` index 404s matter most
— those are the collection URLs the live site has been accruing authority
on, and they resolve to nothing here while their children resolve fine.

**Fix:** a redirect map before Phase 5. `ilanel-studio/reports/redirects-ilanel-live-CORRECTED-2026-08-10.txt`
already exists and should be ported and re-verified against this domain.

### 3. Every page ships without a meta description, `og:image` or Twitter card

162 of 162 indexable pages. Not a subset — all of them, including the
homepage, every product, and every project.

- `description`: absent on 162/162
- `og:image`: absent on 162/162
- `twitter:card`: absent on 162/162

Ross Gardam and Marz both carry a hand-written description and an
`og:image` on every page audited. Ross Gardam's product page:
*"Available in brass, bronze, aluminium and other finishes, Liminal
Pendant Light adapts across a range of architectural environments. Made in
Melbourne."*

The practical effect is not just SERP snippets. Every time someone pastes
an ILANEL product link into a message to a client, WhatsApp, Instagram or
an email, it renders as a naked URL with no image. For a studio whose
entire product is visual, that is the worst possible failure mode.

**Fix:** template-level defaults are enough to start — product description
from the excerpt, `og:image` from the featured image. Hand-write the ~12
commercially important pages.

### 4. The footer's phone number is a placeholder, on all 163 pages

Footer, every page: **`+61 3 9000 0000`**.
`/contact/` body, correct: **`+61 3 9534 1164`**.

The fake number appears on more pages than the real one, and on the two
pages a B2B lead is most likely to be reading (`/trade/`, product pages).
`/trade/` ends with *"please email or call us"* directly above it.

### 5. `/trade/` is a dead end for the B2B funnel

264 words, no form, no email link in the body, no application path, and it
is **not in the primary navigation** (footer only).

The page promises "specialised quote requests, additional photos,
installation guides and a 20% trade discount", then says "if you prefer to
contact us directly, please email or call us" — with no email link and a
placeholder phone number beneath it.

Ross Gardam puts **Specifier** and **Representation** in the primary nav
as first-class items. `ilanel-studio/AGENTS.md` names *"Submit a qualified
brief without needing a phone call first"* as B2B funnel priority #4. That
capability does not currently exist.

---

## P1 — fix before cutover

### 6. 42 news posts resolve at two URLs, and neither matches live

`/news/mdw-final-weekend/` and `/mdw-final-weekend/` both return **200**
with byte-identical HTML (40,122 bytes). No redirect between them.

Both canonicalise to the **root** version — but `ilanel.com` publishes
these at `/news/<slug>`. So the URL that carries the live authority is the
one being canonicalised *away from*, and the root URL — which has no
history — is the one being declared canonical.

The sitemap lists the root variants. The `/news/` archive links to them.

**Fix:** make `/news/<slug>/` canonical, 301 the root variant to it. This
also resolves two duplicate-title collisions (`/falling-leaves/` vs the
product of the same name, `/the-hour-glass-adelaide/` vs the project).

### 7. Ten dead `href="#"` links in the footer of every page

The handover records footer dead links as "wired to these" — FAQ, Warranty
and Trade Programme were. Ten were not, and they appear on all 163 pages
(**1,978 dead links sitewide**):

`Shipping & returns` · `Catalogue` · `Lookbook` · `Materials & finishes` ·
`3D models` · `Product instructions` · `Subscribe` · `Instagram` ·
`Pinterest` · `LinkedIn`

`3D models` appears a second time in the product page body (13 dead links
there).

Three of these are the studio's social accounts, which exist. Three
(`Catalogue`, `Lookbook`, `3D models`) are real resources on `ilanel.com`
today. `Shipping & returns` is a page the FAQ partly covers.

A footer where a third of the links do nothing reads as unfinished to
exactly the specifier audience the site is built for.

### 8. Structured data stops at products

| Template | Schema emitted |
|---|---|
| Product (34) | `Product` + `AggregateOffer`/`Offer` + `BreadcrumbList` + `Organization` ✅ |
| Range archives (5) | `CollectionPage` + `BreadcrumbList` ✅ |
| Projects (51) | `BreadcrumbList` only |
| Light Art (13) | `BreadcrumbList` only |
| News posts (42) | **none** |
| Homepage | **none** |
| `/about/` `/contact/` `/trade/` `/faq/` `/warranty/` | **none** |

59 of 163 pages carry no structured data at all.

`Organization` is emitted on product pages but **not on the homepage** —
which is where crawlers look for it. There is no `WebSite` node anywhere.
Ross Gardam emits `WebPage`, `ImageObject`, `BreadcrumbList`, `WebSite`,
`Organization` and `Product` as a single `@graph` on their product page.

Missing and worth adding: `Organization` + `WebSite` sitewide (studio
name, logo, showroom address, real phone, social profiles — which also
gives the socials a home), `BlogPosting` on news, `CreativeWork` on
projects and Light Art, and `FAQPage` on `/faq/`, which already has ~19
`<details>` Q&A pairs sitting in exactly the right markup for it.

### 9. WordPress defaults and junk URLs are live and indexable

- **`/sample-page/`** — the WordPress default page, published, in the sitemap.
- **`/hello-world/`** — the default post. Fixed *during* this audit by
  commit `3c4de79`; it is now out of `wp-sitemap-posts-post-1.xml` (42
  posts, was 43). The URL itself still returned **200** on a re-check
  after that commit — either page cache or the change is not deployed to
  Cloudways yet. Worth one `curl` to confirm.
- **`/author/aninim/`** — author archive, indexable, no canonical, and it
  discloses the admin username.
- **`/product-category/uncategorized/`** and **`/category/uncategorized/`**
  — two empty taxonomy archives with the identical title "Uncategorized – ILANEL".
- **Three migrated Squarespace junk slugs** are live and in the sitemap:
  `/hw8k1d1w1uhxmd8iyil4qz6asuoy1t-xapez-flmrm-er6jl-gshyc/` and two
  longer variants, plus `/ua0kg76lwjv1moe4n5ltr0z7kyzosv/` and
  `/wn67vqnpn7dl8ohe7lqt88w47wsbo4/`. These exist on live too, so they
  need redirects — but they should not be in the new sitemap as canonical
  destinations.
- **`/wp-json/`** returns a 1.27 MB unauthenticated payload.

### 10. The site is fully indexable — while `ilanel.com` is still live

`robots.txt` allows everything except WooCommerce internals. Only
`/cart/`, `/checkout/` and `/my-account/` are `noindex`. Canonicals point
at `ilanel.dads42.com`.

A complete, crawlable copy of ILANEL's content — including 42 news posts
and 51 projects with near-identical text — is currently competing with the
domain whose search position has been improving (18.5 → 10.4).

**Fix:** `noindex` sitewide, or HTTP auth, until cutover. Remove it as
part of Phase 5. This is a one-line change with an asymmetric downside if
skipped.

### 11. Nine archive pages have no canonical

`/shop/`, all five `/our-range/*`, `/author/aninim/`, and both
`uncategorized` archives. Pagination (`/projects/page/2..6/`,
`/news/page/2..5/`, `/light-art/page/2/`) has neither canonical nor
`rel=prev/next` — 13 more URLs.

### 12. Distinct products share identical titles

| Title | URLs |
|---|---|
| `Supernova – ILANEL` | `custom-round-pendant-supernova`, `custom-linear-pendant-supernova` |
| `Black Rain – ILANEL` | `black-rain-wall-light`, `black-rain-custom-pendant-light` |
| `Cannon Vase – ILANEL` | `cannon-vase-1st-edition`, `colorful-cannon-vase-2nd-edition` |

These are genuinely different products (Round vs Linear, wall vs pendant,
1st vs 2nd edition) rendered indistinguishable in search results, browser
tabs and the sitemap. `OPEN-QUESTIONS.md` §1c flags the Supernova pair as
a data-model ambiguity; the title collision is the visible symptom.

Separately, **93 of 162 titles are under 25 characters** — the pattern is
`<Name> – ILANEL` with nothing else. Ross Gardam uses
`Liminal Pendant Light | Ross Gardam`; Marz uses
`Marz Designs | Australian Architectural & Designer Lighting`. Adding the
product type and "Melbourne" would be a meaningful, cheap gain on a site
whose whole differentiator is *handmade in Melbourne*.

---

## P2 — quality and polish

### 13. Hero text fails contrast on light imagery

The header (logo, hamburger, search, account) and hero copy are **always
white**, over a background image with **no scrim, gradient or overlay**.
`.rg-hero__overlay` exists in the DOM but computes to a transparent
background.

Measured on `/lighting-design-collections/comet-pendant-light/` by
sampling the actual hero image pixels
(`Ilanel_Comet_1800_Blue_FWhite_Black.jpg`, 1500×844):

| Region | Brightest pixel behind the text | Contrast vs white | WCAG AA |
|---|---|---|---|
| Behind H1 | 0.721 | **1.36 : 1** | fails (needs 3:1 large) |
| Behind logo | 0.440 | **2.14 : 1** | fails (needs 4.5:1) |
| Behind top-right icons | 0.411 | **2.28 : 1** | fails |
| Behind hamburger | 0.382 | **2.43 : 1** | fails |

This is not theoretical — the screenshot of that page shows a header that
is effectively invisible. The homepage carousel has the same problem on
one of its three slides (`MatarikiIcon.jpg`).

**This is the fix Ross Gardam already demonstrates:** their header logo is
**black**, sitting on the same kind of pale studio photography. They don't
solve it with a scrim; they solve it by not assuming the image is dark.

Options, cheapest first: a subtle top-to-bottom gradient scrim on
`.rg-hero__overlay`; or extend the existing header state-flip so it
switches to dark type over light heroes.

Because product photography is white-background studio work by nature,
this will recur on most product pages — it is a template issue, not an
imagery one, and it will not be solved by the final photography.

### 14. Automated accessibility is genuinely good

axe-core (WCAG 2.0/2.1 A + AA + best-practice) across five templates:

| Page | Violations |
|---|---|
| `/` | 0 (4 contrast items unresolvable — the hero, above) |
| `/our-range/pendants/` | 0 |
| `/lighting-design-collections/comet-pendant-light/` | 1 — `region` (moderate): `.rg-stickybar` sits outside any landmark |
| `/contact/` | 0 |
| `/projects/` | 0 |

Also verified by hand:

- Focus is visible — the browser's default `outline: auto` is preserved,
  not suppressed. ✅
- Hero carousel controls are real `<button>`s with `aria-label`s
  ("Previous image", "Go to image 3"). ✅
- `prefers-reduced-motion` media queries are present. ✅
- `lang="en-US"` on every page — should be `en-AU`. Trivial.
- No skip link. With landmarks present this isn't a 2.4.1 failure, but 66
  focusable elements precede the main content on every page.
- **50 touch targets under 24×24 px** on the product page. The hero dots
  are **40×2 px**. Header controls are 24×13 px. Breadcrumb links are
  13 px tall. Not a WCAG 2.1 AA failure (target size is 2.2), but it will
  be felt on a phone.
- `.rg-hero__slide.is-active` carries opacity 0.017 while an
  unclassed sibling carries 0.98 — the class and the animated state are
  out of sync. Harmless visually; it makes the carousel untestable by
  class and will confuse the next person who touches it.

### 15. Performance is fine, with one avoidable weakness

`/lighting-design-collections/comet-pendant-light/`: 33 requests, ~704 KB
transferred, DOM ready 669 ms, load 2,150 ms.

- **TTFB 600 ms** — the largest single component. No page cache is
  evident. Cloudways ships Varnish/Redis; enabling it is a settings change.
- **853 of 1,063 images across the site have no `width`/`height`** — a
  direct CLS risk, and the cheapest Core Web Vitals win available.
- One image is served at 690 KB (`Pipi_GAI.png`) — a PNG where WebP is
  used elsewhere. Three images are served at ≥2× their display size
  (1024×1024 into a 468×535 box).
- 141 images carry `alt=""`. Correct for decorative images; worth
  spot-checking that no product photography is in that set.

### 16. Visual parity vs the references

Where the build genuinely matches Ross Gardam: page anatomy (hero →
breadcrumbs → storytelling rows → configurator → downloads → discover
more), three-up portrait product tiles, the `LABEL /` trailing-slash
convention, generous section padding, the transparent overlaid header, and
the sticky enquiry bar. The scale drawing and lit/unlit toggle are
additions the reference doesn't have and are the strongest UX ideas on the
site.

Where it diverges, and where the "as if we stole it from him" bar is not
yet met:

| | Ross Gardam | ILANEL build |
|---|---|---|
| Heading typeface | `Favorit Std` — sans, throughout | **`Playfair Display` — serif** |
| Body | Favorit Std 16px | Inter 15px |
| Header type colour | Black on pale imagery | White always |
| Primary nav | About · Products · Objects & Editions · Journal · **Specifier** · **Representation** · Contact | Home · Products · 5 ranges · Projects · Light Art · News |
| Icon labels | — | — |
| Forms on homepage | 3 | 0 |
| Section padding | 156–167 px top | comparable ✅ |

The typeface is the biggest single divergence. Ross Gardam's identity is
an all-sans system; the serif H1 reads as a different studio's brand
language. That may well be a deliberate ILANEL brand decision — the
`ilanel-studio` brand book would settle it — but it should be a decision,
not drift, because it is the first thing the studio will see when they
compare the two side by side.

The navigation gap is the more commercially consequential one. **`/about/`,
`/contact/` and `/trade/` are not in the primary navigation at all** —
they exist only in the footer. Both reference sites put About and Contact
in the header, and Ross Gardam gives the B2B path two dedicated top-level
slots.

Marz adds two devices worth stealing: text labels beside the icons
("Menu", "Search", "Shopping Bag" with a live count), and an announcement
bar. ILANEL's equivalent of their free-shipping bar would be the lead
time — *"Made to order · 4–12 weeks · Melbourne"* — which is currently
buried in the product spec block.

### 17. Smaller content items

- `/about/` has **three `<h1>`s** ("Our Story", "ABOUT ILAN", "OUR
  CLIENTS"); `/terms-and-conditions/` has two, both saying the same thing
  in different punctuation.
- The `/news/` archive renders each post title as an `<h1>` — 10 per page.
- `/warranty/` is 296 words and `/trade/` 264 — the two pages a specifier
  reads before committing are the two thinnest on the site.
- Product downloads point at **`https://www.ilanel.com/s/ILANEL_COMET_PENDANT_2026_V2-c772.pdf`**
  — the Squarespace domain being retired. Every spec PDF is a live
  dependency on the site Phase 5 turns off. These need migrating into
  `wp-content/uploads/` before cutover.
- Homepage has four CTAs total, one of which (`Subscribe`) is dead.

---

## What is already right

Worth recording, because the next session will be tempted to change it:

- Product URLs match `ilanel.com` exactly, including the `/editions/` split.
- `AggregateOffer` with real `lowPrice`/`highPrice`, in **AUD** — verified
  live in the served schema.
- The product template is genuinely strong: configurator, live price, lead
  time, ETA dispatch date, real specification block, scale drawing,
  lit/unlit toggle, related products, project cross-links.
- Breadcrumbs with `BreadcrumbList` on products, ranges, projects and Light Art.
- Automated accessibility is cleaner than most commercial sites.
- Page weight and request count are well controlled.
- The 404 page returns a real 404 status and a branded template.
- `/news/` pagination works to page 5; `/projects/` to page 6.

---

## Suggested order of work

**Before anything else** — one line, removes an active risk:
`noindex` the staging domain (§10).

**Phase 3 (content parity), add:**
1. Enquiry form on `/contact/`, trade form on `/trade/`, newsletter in
   footer; repoint "Buy now" at the cart (§1)
2. Real phone number in the footer (§4)
3. Wire or remove the 10 dead footer links (§7)
4. Meta description + `og:image` templates (§3)
5. `About` / `Contact` / `Trade` into the primary nav (§16)

**Before Phase 5 (cutover):**
6. Redirect map for the ~63 404ing legacy URLs (§2)
7. `/news/<slug>/` canonical + 301 from root (§6)
8. Migrate spec PDFs off `ilanel.com` (§17)
9. Delete `/sample-page/`, `/hello-world/` from the sitemap, `/author/*`,
   the empty taxonomy archives (§9)
10. Unique titles for the three colliding product pairs (§12)

**Quality, any time:**
11. Hero scrim or dark-header state flip (§13)
12. `width`/`height` on images; enable Cloudways page cache (§15)
13. `Organization` + `WebSite` on the homepage; `BlogPosting`, `FAQPage` (§8)
14. Settle the serif-vs-sans question with the studio (§16)

---

## Method and reproducibility

- Crawl: `fetch()` from a browser on the site's own origin, seeded from
  `wp-sitemap.xml` (9 sub-sitemaps, 162 URLs) plus link discovery from the
  homepage and product/range/project templates. 175 unique URLs parsed
  with `DOMParser`.
- Migration parity: 163 paths from
  `ilanel-studio/reports/live-urls-2026-08-10.txt`, each fetched with
  `redirect: 'follow'`, status and final URL recorded.
- Accessibility: axe-core 4.8.2, rulesets `wcag2a`, `wcag2aa`, `wcag21a`,
  `wcag21aa`, `best-practice`.
- Contrast: hero background images drawn to `<canvas>`, per-pixel relative
  luminance (WCAG formula) sampled over the header band and the H1 band,
  worst-case (brightest) pixel reported.
- Performance: Navigation Timing + Resource Timing, warm cache.
- References: rendered DOM and `getComputedStyle` on
  `rossgardam.com.au` (home + `/product/liminal-pendant-light/`) and
  `marzdesigns.com` (home).
- Raw counts: `docs/audit-2026-08-15-evidence.json`.

**Not covered:** true mobile-width rendering (the browser viewport could
not be reduced below 1026 px in this environment — breakpoints at 1100 /
900 / 760 / 720 / 600 / 374 px exist in CSS but were not visually
verified), checkout completion, and email deliverability.

**Note:** the site changed during the audit — `/hello-world/` was deleted
mid-run (commit `3c4de79`) and the working tree carried uncommitted
changes to `main.css`, `product.js` and `single-product.php` throughout.
Anything in §9 and §13 may have moved since; re-verify before acting.
Nothing in P0 depends on those files.
