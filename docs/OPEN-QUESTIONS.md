# Open questions — POC

Things the POC needs but cannot answer for itself. Nothing here is a blocker
for reviewing the *structure*; all of it is a blocker for treating the POC as
real.

---

## 1. Prices — ✅ RESOLVED (2026-08-11)

Real prices now come from the authenticated Squarespace Commerce export and
live in `data/products.json` → `commerce.variants[].price`:

| Product | Variants | Real range |
|---|---|---|
| Comet | 36 (Size × Color × Glass) | $3,195.50 – $7,437.72 |
| Kahdu | 24 (Color × Shape) | $1,870.00 – $3,080.00 |
| Dais | 4 (Glass) | $2,094.40 – $2,818.20 |
| Comet Stardust | 3 (Size) | $3,572.80 – $4,188.80 |

The old invented figures (`2450.00` etc.) are gone from both seeders.

`scripts/seed-products.php` (the WP-CLI seeder) was stale until 2026-08-12 —
it still built `WC_Product_Simple` with the demo prices while the blueprint
seeder built variable products. It now mirrors the blueprint generator:
variable products, real attributes and variants, `WC_Product_Variable::sync()`,
and it seeds projects and the product↔project relation too.

## 2. SKUs — ✅ RESOLVED (2026-08-11)

Real SKUs ship with the variant data (e.g. `SQ0540475`). The `DEMO-*` override
was removed from the blueprint seeder, and as of 2026-08-12 the WP-CLI seeder
matches — `DEMO-*` is now only used for products with no variant data, of
which there are currently none.

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
