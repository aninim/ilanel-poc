# Studio tasks and questions

Everything in `docs/LAUNCH-PLAN.md` that needs ILANEL/the studio's input,
a decision, a credential, or physical work (photography) — pulled into one
place so it can be handed off directly, instead of scattered across the
engineering plan. Cross-references point back to the plan for full context
on *why* each item matters; this file is the checklist, not the reasoning.

Last updated: 2026-08-14.

---

## Blocking — needed before real checkout can go live

### 1. Squarespace Commerce API access

**Status:** blocked on a Squarespace plan upgrade that only takes effect
at the end of the current billing cycle — confirmed 2026-08-14, not
gettable sooner by any means tried.

**What's needed:** once the plan upgrades, whoever holds Squarespace
owner/admin access generates an API key at Settings → Advanced →
Developer API Keys, with Products-Read scope. Five-minute task once the
plan is active.

**Why it matters:** 30 of 34 products currently show a flat $3,450
placeholder price (real prices vary per order, so this was a deliberate
launch workaround, not an error) — this is the only way to pull the real
per-product/per-variant prices and SKUs from Squarespace's Commerce
system. See `LAUNCH-PLAN.md` Phase 1 and `OPEN-QUESTIONS.md` §1–2.

### 2. Payment gateway

**Question:** does ILANEL already have a Stripe or PayPal account from
elsewhere (current invoicing, another sales channel)? If so, which, and
can it be reused — likely faster than a new signup.

**If not:** WooCommerce Payments (Stripe-backed) is the default
recommendation — free WooCommerce.com account, needs ILANEL's ABN and
bank details for business verification.

**Why it matters:** nothing can actually process a payment until this is
answered — every other Phase 2 checkout item is either done or waiting on
this. See `LAUNCH-PLAN.md` §2a.

### 3. Real shipping cost

**Question:** what does ILANEL actually charge to ship a made-to-order
pendant/chandelier within Australia? Is international shipping in scope
at launch, or Australia-only for now?

**Current state:** the live shipping zone is configured and working
(`scripts/configure-commerce.php`, deployed 2026-08-14) but the freight
line item is a deliberate **$0.00 placeholder**, clearly labeled "TBC,
confirm with studio" — checkout works end-to-end but doesn't yet charge
anything real for freight.

**Why it matters:** blocks a real, honest test order. See `LAUNCH-PLAN.md`
§2b.

### 4. Checkout UX — instant-buy vs. quote-only

**Question:** now that every product shows a price, should "BUY NOW"
actually mean instant purchase for a $3,000–7,000+ made-to-order light, or
should some/all products stay on a "request a quote" path even with a
price shown?

**Why it matters:** this is a real business/brand decision, not something
to assume from the code — made-to-order lighting at this price point may
not want an instant-buy cart at all. See `LAUNCH-PLAN.md` §2c.

---

## Content and structure decisions

### 5. Range taxonomy

**Question:** are Pendants / Wall Lights / Chandeliers / Lamps / Editions
the right five categories, and are they named/grouped the way ILANEL
actually thinks about the catalogue? Currently inferred from product type
labels and the live `/editions/` URL split — not studio-approved.

**Why it matters:** this determines the site's real navigation and archive
URLs. Changing it after launch means real URL churn (bad for SEO/
backlinks). Confirm before, not after, cutover. See `LAUNCH-PLAN.md` §3c,
`OPEN-QUESTIONS.md` §5.

### 6. Newsletter scope

**Question:** Oren flagged that ILANEL's "Journal" now also means
newsletter content, separate from the "News" section already migrated
(42 posts, live at `/news/`). Which is it:

- (a) An actual email newsletter signup/send capability — needs a new ESP
  integration (Mailchimp, Klaviyo, etc.), nothing like this exists yet, or
- (b) Newsletter *content* that should also appear as News posts — may
  already be covered by the 42 migrated posts, needs a check for overlap
  before building anything new.

**Why it matters:** these are two completely different builds. Don't want
to guess and build the wrong one. See `LAUNCH-PLAN.md` §3d.

### 7. Low-priority static pages

**Question:** `/3d-models`, `/linktree`, `/customer-satisfaction-survey`
exist on ilanel.com but are unlinked from its own navigation ("Not
Linked" in their admin) — worth carrying over, or already intentionally
retired content?

**Why it matters:** avoids migrating dead content just because it
technically still resolves. See `LAUNCH-PLAN.md` §3a.

---

## Physical / asset work (not engineering)

### 8. Finish photography — Comet's remaining 24 variations

**Status:** Comet's configurator correctly matches 12 of 36 finish
variations to a real photo (fixed 2026-08-13). The other 24 have **no
photo to match because the photo doesn't exist yet** — confirmed this is
a real content gap, not a matching bug.

**What's needed:** one photograph per remaining Commerce `Color` value —
the studio shoots or supplies these directly.

**Why it matters:** the configurator silently falls back to the parent
hero image for unmatched variations right now — not broken, just
incomplete. See `LAUNCH-PLAN.md` §3b, `OPEN-QUESTIONS.md` §3a.

---

## Access and account admin (not code)

### 9. Confirm who manages ilanel.com's DNS

**Question:** ahead of cutover, who actually holds the registrar login for
`ilanel.com`? Not necessarily the same account/person as `dads42.com`'s
Cloudflare setup used for the demo.

**Why it matters:** Phase 5 cutover can't point DNS at the new server
without this. See `LAUNCH-PLAN.md` §5.3.

### 10. Freeze date for Squarespace edits

**Question:** when can ILANEL stop making direct edits to the live
ilanel.com/Squarespace site, so a final content sync can run cleanly
before DNS cuts over?

**Why it matters:** anything edited on Squarespace after the final sync
and before cutover is lost unless caught in time. See `LAUNCH-PLAN.md`
§5.1 and the "not decided" list at the top of that file.

### 11. Studio admin walkthrough

**Status:** not yet scheduled. Per the existing plan, the studio manages
ongoing content directly via WordPress admin post-launch.

**What's needed:** confirm the studio is actually comfortable in the
WP-admin UI — don't assume from having access alone. A short walkthrough/
training session once the site is closer to launch. See `LAUNCH-PLAN.md`
Phase 6.

---

## Already resolved — kept here for reference, no action needed

- ✅ Real prices/SKUs for 4 products (Comet, Kahdu, Dais, Comet Stardust)
- ✅ Static pages (About, FAQ, Trade, Warranty, Privacy, Terms, Contact)
- ✅ Hosting, currency/country, security hardening, backups confirmed
  configured (restore itself still untested — see `LAUNCH-PLAN.md`
  Phase 4)
- ✅ AU tax/shipping infrastructure (GST, zones) — only the real freight
  number (§3 above) is still open
