# Open questions — POC

Things the POC needs but cannot answer for itself. Nothing here is a blocker
for reviewing the *structure*; all of it is a blocker for treating the POC as
real.

---

## 1. Prices — placeholder

`data/products.json` carries `"price": "PLACEHOLDER"` for every product, and
the seeder deliberately skips setting a price when it sees that value.

Consequence: **products seed without a price, so the Offer node is omitted.**
The schema harness used an invented `2450.00 AUD` to prove the Offer generator
works — that figure is not an ILANEL price and must not be quoted.

**Needed:** real RRP per product from Commerce. Note the handoff's warning that
the production store holds **73 inventory records against 25 product pages** —
confirm which record is authoritative per product before pulling figures.

## 2. SKUs — placeholder

Same pattern. Real SKUs come from Commerce.

## 3. Finishes — not captured

The live pages reference a "ColoUr Disclaimer" but the finish lists themselves
are in the spec PDFs, which the crawl recorded as URLs but did not parse:

- `ILANEL_COMET_PENDANT_2026_V2-c772.pdf`
- `ILANEL_COMET_STARDUST_PENDANT_23V1.pdf`
- `ILANEL_KAHDU_PENDANT_2026_V1.pdf`
- `ILANEL_DAIS_WALL_2026_V2.pdf`

**Needed:** the finish list per product. Extracting from the PDFs is
straightforward once someone confirms they're current.

## 4. Photography — not included

No product images. The studio is supplying photography, so the POC renders
grey placeholder blocks. The gallery markup and `image` schema field are wired
and will populate as soon as images exist.

## 5. Range structure — assumed

The POC assumes two ranges: **Pendants** (Comet, Comet Stardust, Kahdu) and
**Wall Lights** (Dais). That's inferred from product type labels on live, not
from a studio taxonomy.

Ross Gardam runs **14 ranges and 24 collections** across 67 products. ILANEL's
real range structure is a studio decision and will change the archive pages.

## 6. Product vs variant — unresolved

Bears directly on the POC's data model:

- Live has **two** Supernova products (Round and Linear) where staging has one.
- **Stiria vs Stella** may be a rename or two products.
- 73 inventory records vs 25 pages suggests many records are variants.

If ILANEL's catalogue is largely **variable products** (one product, many
finishes/sizes) rather than simple ones, the schema needs `AggregateOffer`
instead of a single `Offer`, and the seeder needs rewriting for
`WC_Product_Variable`.

**This is the one open question that could change the code**, not just the
data. Codex's browser pass (A1, A2, A6 in `CODEX-BRIEF-2026-08-10.md`) should
settle it.

## 7. Journal / projects cross-linking — not built

The platform recommendation rests partly on products↔projects cross-linking
being native in WordPress. The POC's scope is product + range pages, so it
isn't demonstrated. `data/products.json` carries the Kahdu → Chatswood Hill
Tavern relationship, ready to wire when scope extends.

## 8. Hosting — unresolved

`dads42.com` is Firebase Hosting (static) and cannot run WordPress. See the
README. **This is what stands between the POC and being clickable.**
