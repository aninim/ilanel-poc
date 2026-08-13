# ILANEL — Launch Plan

**Status: active plan, started 2026-08-14.** Supersedes `PHASE-2-PLAN.md`
(POC phase, complete) and the POC framing in `README.md`. This is the plan
to take ILANEL from a Squarespace portfolio site to a live, fully
functioning WordPress + WooCommerce store at `ilanel.com`, with real online
checkout, replacing Squarespace entirely.

**Decided, not open questions:**

- **Full cutover.** `ilanel.com`'s DNS gets repointed at the new site.
  Squarespace is retired. This is the real migration, not a parallel demo.
- **Real online checkout.** Customers pay in full at checkout via a real
  payment gateway. Not enquiry-only, not deposit+balance (may change later —
  that's a WooCommerce settings change, not an architecture one).
- **Domain/DNS, Commerce API access, and hosting budget are Oren's to
  get or already has.** Not blocked on the studio for those three.
- Current state before this plan: a working demo at
  `https://ilanel.dads42.com`, real WordPress + WooCommerce, 34 products /
  51 projects / 13 Light Art / 42 News posts seeded, all built this session
  and prior — see `docs/HANDOVER-2026-08-13.md` for what shipped and why.

**Not decided — surfaced here, not guessed at:**

- Payment gateway (Stripe vs PayPal vs both) — §3.
- Whether ILANEL wants to keep making direct Squarespace edits during the
  build window, and for how long — §7.
- Go-live date / freeze window.

---

## The shape of the work

Six phases. Each has a **gate** — the thing that must be true before the
next phase starts — because several of these depend on access or decisions
this plan can't manufacture.

```
1. Real data      →  2. Payments      →  3. Content parity  →
4. Pre-launch QA  →  5. Cutover       →  6. Post-launch
```

Phases 1–2 can run in parallel with 3 once each is unblocked. 4–6 are
strictly sequential.

---

## Phase 1 — Real product data (blocks real prices, SKUs, stock)

**Gate to start:** an authenticated Squarespace Commerce API key with
Products-Read scope. Confirmed three separate ways this session that no
unauthenticated endpoint exposes it — this is not a scraping problem,
it's a credential ILANEL's Squarespace account owner has to generate.

**Gate to finish:** all 34 products (not 4) carry real `commerce.attributes`
/ `commerce.variants` in `data/products.json`, matching the shape Comet,
Kahdu, Dais and Comet Stardust already have.

### 1a. Get the Commerce API key

Squarespace Settings → Advanced → API Keys, or ILANEL's account owner grants
Oren temporary API access. This is a five-minute task for whoever has
Squarespace owner/admin access — likely the actual blocker is *finding out
who that is and getting them to do it*, not the technical step itself.

### 1b. Pull real commerce data for the remaining 30 products

`scripts/scrape-catalogue.js` already has the scraping logic; it needs the
authenticated endpoint instead of guessing from public HTML. Re-run against
all 34 slugs, merge into `data/products.json` via the existing
`merge-catalogue.js` (already has the idempotency/never-downgrade guards
from a prior real incident — see `project_ilanel_data_pipeline` memory).

### 1c. Resolve the two data-model ambiguities

From `docs/OPEN-QUESTIONS.md` §6, not yet resolved:

- Live has **two** Supernova products (Round, Linear) where our data has one
  combined — confirm as two real product pages, not one with a size option.
- **Stiria vs Stella** — rename or genuinely two products? Check the
  Commerce record IDs, not just the names.

Both are answerable directly from the Commerce API response once 1a lands —
don't guess from the public site.

### 1d. Re-seed

Run `scripts/seed-products.php` against the live Cloudways install with the
completed `data/products.json`. Every product should then carry a real
`AggregateOffer` (or `Offer` for simple products) — verify per the existing
pattern (`curl | grep AggregateOffer`), same checks already used this
session.

**Done when:** `data/products.json`'s summary shows 34/34 with real
commerce data, not 4/34. No placeholder prices remain anywhere in the
seeded WordPress database (`wp post meta list --meta_key=_price` should
show no obvious defaults like `2450.00`/`2680.00` etc., the old demo
figures already flagged as dead in `PHASE-2-PLAN.md`).

---

## Phase 2 — Real checkout

**Gate to start:** Phase 1 substantially underway (checkout can be built
against the 4 products with real prices already, doesn't need to wait for
all 34).

**Gate to finish:** a real order, placed with a real card in test mode,
completes end-to-end — payment captured, order confirmation email sent,
order visible in WooCommerce admin.

### 2a. Pick and install a payment gateway

WooCommerce Payments (Stripe-backed, native WooCommerce integration, no
extra plugin licensing) is the default recommendation — it's what most new
WooCommerce stores use, handles AU card processing and Apple/Google Pay out
of the box, and needs only a WooCommerce.com account (free) plus a
Stripe-equivalent business verification (ILANEL's ABN, bank details).

**Needs a decision from Oren/ILANEL, not assumed:** if ILANEL already has a
Stripe or PayPal account from elsewhere (their current invoicing, another
sales channel), reusing it may be faster than a new WooCommerce Payments
signup. Ask before building against a specific gateway.

### 2b. Wire AU tax and shipping properly

`scripts/build-playground-blueprint.js` already configures GST 10%
inclusive, AU-only shipping/billing, and a flat-rate + local-pickup shipping
zone — but only for the Playground demo path, with an **offline** gateway
stubbed in so checkout completes without a real processor. Port the
tax/shipping config (not the offline gateway) into the real seeder or a new
`scripts/configure-commerce.php`, matching what `seed-products.php` /
`seed-journal.php` already do for their respective concerns.

Confirm real shipping cost/logic with ILANEL — the blueprint's flat rate is
a placeholder never validated against what ILANEL actually charges for a
made-to-order pendant light shipped within Australia (and international,
if that's in scope — currently unconfirmed).

### 2c. Checkout UX

`woocommerce/single-product.php` already replaces Woo's default add-to-cart
with a "BUY NOW / SEND ENQUIRY / SPECIFIER ENQUIRY" pattern
(`ilanel_poc_render_enquiry()` in `functions.php`) for products without a
purchasable price. Once every product has a real price (post-Phase 1),
audit which of those three CTAs should actually route to cart vs. stay
enquiry-only — made-to-order lighting at this price point may legitimately
want a "request a quote" path even with a real price shown, rather than an
instant-buy cart. **This is a business decision, not an engineering one** —
ask before assuming every product becomes instant-checkout.

### 2d. Test order end to end

Stripe/WooCommerce Payments test mode, a real (test) card number, confirm:
order status transitions correctly, confirmation email fires (needs SMTP —
see Phase 4), the order appears correctly in `wp-admin` → WooCommerce →
Orders with the right line items, tax, and shipping.

---

## Phase 3 — Content parity (the "other missing parts" from the audit)

Everything found in the 2026-08-13 sitemap audit that's real, live content
on ilanel.com with no equivalent here yet. Not blocked on anything — can
run in parallel with Phases 1–2.

### 3a. Static pages

`/about`, `/contact`, `/faq`, `/services`, `/trade`, `/warranty`,
`/privacy-policy`, `/terms-and-conditions` — real pages on ilanel.com,
already linked (dead `href="#"`) from this site's footer. Scrape the real
copy from each (same read-only method used for products/projects/news),
build as WordPress `page`s, wire the footer links to them instead of `#`.

`/3d-models`, `/linktree`, `/customer-satisfaction-survey` — lower
priority, confirm with ILANEL whether these are worth carrying over or were
already abandoned on the live site (their own admin lists them as
"Not Linked" — some may be intentionally retired, not just
disorganized nav).

### 3b. Finish photography gap

`docs/OPEN-QUESTIONS.md` §3a — Comet's configurator now correctly matches
12/36 variations to a real photo (fixed 2026-08-13, via
`ILANEL_Product_Meta::swatch_for_option()`'s token match). The other 24
have no photo to match *because the photo doesn't exist yet*, not because
of a code gap. **Needs the studio to shoot or supply one photograph per
remaining Commerce `Color` value** — this is asset production, not
development.

### 3c. Range taxonomy confirmation

`docs/OPEN-QUESTIONS.md` §5 — the five ranges (Pendants, Wall Lights,
Chandeliers, Lamps, Editions) are inferred from product type labels and the
live `/editions/` URL split, not a studio-approved taxonomy. Confirm with
ILANEL before launch — this determines the site's actual navigation
structure, and changing it after launch means real URL churn on
`/our-range/*` archive pages.

### 3d. Email newsletter (separate from News)

Oren flagged that ILANEL's "Journal" now also covers newsletter content,
separate from the News section already migrated. Scope unclear — confirm
whether this means: (a) an actual email newsletter signup/send capability
(needs an ESP integration — Mailchimp, Klaviyo, etc. — net-new, not in any
existing code), or (b) newsletter *content* that should also appear as News
posts (may already be covered by the 42 migrated posts — check for overlap
before building anything new).

---

## Phase 4 — Pre-launch technical QA

**Gate to start:** Phases 1–3 substantially complete.

- **SMTP / transactional email.** Order confirmations, contact form
  submissions, password resets need real outbound email — Cloudways'
  default PHP `mail()` is unreliable for this at production volume. Wire a
  real provider (SendGrid, Postmark, or Cloudways' own add-on) before
  launch, not after the first lost order-confirmation email.
- **Backups.** Cloudways has this built in; confirm it's actually
  configured and a restore has been test-run once before go-live, not
  just assumed to work.
- **SSL** — already working on `ilanel.dads42.com` (Let's Encrypt via
  Cloudways); re-verify after the domain changes to `ilanel.com` — a new
  cert has to provision for the new hostname, this doesn't carry over
  automatically.
- **Performance pass** — the product/project templates already have image
  size constraints and lazy loading built in (per `PHASE-2-PLAN.md`'s
  "SLOW HEROES" fix); re-run a real page-speed check once all 34 products'
  real photography is loaded, not just the 4 that were dressed during POC.
- **Broken-link sweep** — automated crawl of the live Cloudways instance
  once Phase 3's static pages exist, to catch any remaining dead `href="#"`.
- **Security review** — WordPress + WooCommerce is a bigger attack surface
  than a static POC demo. Confirm WooCommerce/WordPress core are on latest,
  a security plugin (Wordfence or similar) or Cloudways' built-in
  protections are active, and admin accounts use strong unique passwords
  (not the auto-generated Cloudways password still sitting in this
  conversation's history — rotate it regardless of launch timing).

---

## Phase 5 — Cutover

**Gate to start:** Phase 4 fully green. This is the one-way door — treat it
with the caution the rest of this project has used for anything touching
the live site.

1. **Freeze Squarespace content changes** — agree a cutoff date with
   whoever edits ilanel.com today, so nothing changes there after the final
   scrape/migration pass and before DNS repoints.
2. **Final content sync** — re-run the News/Projects/Light Art scrapers one
   last time to catch anything published on Squarespace between the
   original scrape and cutover.
3. **Point `ilanel.com`'s DNS at the Cloudways server** (same mechanism
   already used for `ilanel.dads42.com` — A records, Cloudways SSL
   provisioning) — but this time on a domain neither of us controls the
   registrar for by default assumption; confirm who actually manages
   ilanel.com's DNS (a registrar login, not necessarily the same
   Cloudflare account used for `dads42.com`).
4. **Update WordPress's own `siteurl`/`home`** and run the domain
   search-replace across the database — same steps already proven on the
   `dads42.com` cutover earlier this session (`wp search-replace`), just
   pointed at `ilanel.com` this time.
5. **Redirect map** — Squarespace's own URLs vs. this site's final URLs:
   confirmed identical for Projects/Light Art/News/most products; the
   `/lighting-design-collections/` vs `/editions/` split already matches
   live. Anything that doesn't match 1:1 needs a real 301, not a 404 —
   audit this explicitly right before cutover, not assumed clean from the
   sitemap audit alone (which was a snapshot, not a guarantee).
6. **Verify Google Search Console** — add/confirm the new site in GSC,
   submit the sitemap, watch for crawl errors in the days after cutover.
   The whole point of the backlink-preservation work earlier is measured
   here, not assumed.

---

## Phase 6 — Post-launch

- **Monitor** — Cloudways monitoring + GSC crawl reports for the first
  1–2 weeks. Fix any 404s or broken redirects found, don't wait for a
  scheduled review.
- **Studio handoff** — per the earlier decision, the studio manages
  ongoing content via WP admin directly. Confirm they're actually
  comfortable in the admin UI (a short walkthrough/training session,
  not assumed from having access).
- **Agent/skills layer** — Oren's stated intent to build tooling on top of
  WP admin for things the UI makes tedious (bulk price updates, new
  product intake, etc.) — scope this once the studio has used the real
  admin for a few weeks and it's clear what's actually tedious for them,
  not guessed at in advance.

---

## What's explicitly NOT in this plan

Carried over from `PHASE-2-PLAN.md`'s hard locks, still true:

- Never write to Squarespace during any of this — every scrape/read stays
  read-only until the moment Phase 5 step 3 repoints DNS.
- Never edit live Commerce inventory directly.
- Photography stays ILANEL's own, sideloaded — no stock imagery substitution.

Not scoped here, flagged for a separate conversation if it turns out to
matter: multi-currency/international shipping, a customer account/wishlist
system beyond WooCommerce's defaults, any ERP/inventory-system integration
beyond what Squarespace Commerce already provided.
