# Projects / exhibition design spec — Ross Gardam and ILANEL

Measured 12 August 2026. This is a rendered-page specification, not a redesign proposal.

## Executive answer: does Ross Gardam have a Projects section?

**No — Ross Gardam does not have a dedicated Projects index/archive.** Project work exists, but it is published as items in the mixed **Journal**:

- nearest index: <https://www.rossgardam.com.au/journal/>
- representative project item measured here: <https://www.rossgardam.com.au/article/orrong-by-studio-cobe/>

Evidence:

- The primary navigation and footer expose `Journal`, not Projects.
- `/projects/` is a 404.
- `/journal/projects/` resolves back to the unfiltered `/journal/`; it is not a separate archive.
- `/case-studies/` and `/collaborations/` are 404s.
- The sitemap index contains `page-sitemap.xml`, `product-sitemap.xml`, `journal-sitemap.xml`, `product_range-sitemap.xml`, and `product_collection-sitemap.xml`. It has no project or journal-category sitemap.
- On the Orrong item, `Projects` appears as plain breadcrumb/category text, not an archive link.
- The Journal DOM has no category/filter control. It mixes product announcements, studio news, exhibitions, and architectural projects.

Accordingly, `pj-rg-index*` captures the actual Journal, not a nonexistent project-only listing.

## Captures and method

All requested URLs returned HTTP 200 and all 12 captures succeeded. Desktop captures use a 1440 × 900 CSS-pixel viewport at DPR 1. Mobile captures use iPhone 13 device emulation at 390 × 664 CSS pixels, touch enabled and DPR 3; the PNGs are therefore 1170 physical pixels wide. Pages were slowly scrolled before capture to load lazy media and complete Squarespace `preFade` reveals.

Files:

- `pj-rg-index.png`, `pj-rg-index-mobile.png`
- `pj-rg-item.png`, `pj-rg-item-mobile.png`
- `pj-ilanel-projects-index.png`, `pj-ilanel-projects-index-mobile.png`
- `pj-ilanel-projects-item.png`, `pj-ilanel-projects-item-mobile.png`
- `pj-ilanel-lightart-index.png`, `pj-ilanel-lightart-index-mobile.png`
- `pj-ilanel-lightart-item.png`, `pj-ilanel-lightart-item-mobile.png`

Measurements marked **DOM** are from `getBoundingClientRect()` and `getComputedStyle()` after layout. RG CSS citations refer to the supplied one-line bundle `rg-styles/header.min.css`. ILANEL uses generated Squarespace CSS; the relevant site override is:

`https://static1.squarespace.com/static/custom-css/649c6e2b5e430e5270af584a/649c6e2b5e430e5270af5852/3/custom.css`

Viewport height was held at 900px for both desktop-width measurement passes. This matters for ILANEL sections sized in `vh`.

---

## Index/listing 1 — Ross Gardam Journal (the project equivalent)

URL: <https://www.rossgardam.com.au/journal/>

### 1. Grid

| viewport | columns | measured tile/image width | behaviour |
|---|---:|---:|---|
| 1440 | 3 | column 422.61px; image 394.61px | fixed equal columns; not masonry |
| 1024 exact | 3 | column 317.30px; image 289.30px | fixed equal columns |
| 390 | 1 | column 358px; image 330px | stacked |

The CSS breakpoint is worth preserving exactly: `.section-tiles .grid .grid__col--1of3` changes to 50% at `max-width:1023px`, then 100% at `max-width:767px`. Thus **1024 exactly is still three columns; 1023 and below is two until the 767px one-column breakpoint**. Source: `rg-styles/header.min.css`.

The nine ordinary cards are uniform-width rows. Their heights differ with copy length, so this is flex-wrap with row height set by the tallest card, not masonry. Above them is one deliberately larger featured article: a 1061 × 636.59px (5:3) background image inside the 1240px shell at 1440. That featured treatment is the only larger item.

### 2. Tile anatomy

Featured article:

- Section title `Journal`: 28px / 32.2px, weight 400.
- Image is a background crop at 5:3, `background-size:cover`; measured selector `.section--intro .section__image`.
- A vertical `FEATURED ARTICLE /` label sits at the image's right edge.
- Title is beneath/right of the image: 24px / 25.2px, weight 400; CTA below it.

Ordinary cards:

- Image: rendered 1.868:1 (first card 394.61 × 211.23px; natural file 1500 × 803), width 100%, height auto. Selector `.tile .tile__image img`.
- Copy: synopsis only—there is no distinct title, location, year, or category. At desktop it is 16px / 20.8px, weight 300, `letter-spacing:.16px`; selector `.tile .tile__content p`.
- CTA: `EXPLORE PROJECT /`, 16px / 19.2px, weight 400, uppercase. It becomes 12px / 14.4px on mobile.
- Text is beneath the image, aligned left, with `.tile__content{max-width:46.2rem;padding:0 .3rem}`.

### 3. Gaps

- Horizontal: 28px between image edges, created by 14px left/right column padding and the grid's -14px outer margin.
- Image to copy: 37.5px desktop; 20px mobile. Source: `.tile .tile__image{margin-bottom:3.75rem}` and its mobile override in `rg-styles/header.min.css`.
- Row/vertical allocation: 125px bottom padding per column at desktop, 70px at `max-width:1023px`. Because copy heights vary, the visible content-to-content gap is not a single fixed number; 125/70px is the traceable row rhythm.
- Outer shell: 100px each side at 1440, 50px at 1024 exact, 30px at 390. Source: `.shell` in `rg-styles/header.min.css`.

### 4. Filtering, sorting, pagination

- No filtering or sorting.
- Initial load: one featured article plus nine ordinary cards.
- Pagination is a centered `LOAD MORE` button, 200 × 48.8px, 14px uppercase, with 15px × 25px padding.
- Total number of Journal records after repeatedly loading more: **UNKNOWN** (not needed to establish initial load and not exercised).

### 5. Hover

There is **no image zoom, overlay, or caption reveal**. The image remains unchanged. Hovering the image activates the adjacent CTA treatment:

- underline pseudo-element moves from `scaleX(0)` to full scale;
- property `transform`, duration `.4s`, default easing `ease`;
- selector `.tile .tile__image:hover + .tile__content .link:after`;
- base selector `.link:after{...transition:transform .4s}`;
- file `rg-styles/header.min.css`.

The linked radial-glow `:before` also becomes opaque on fine-pointer hover. Mobile has no hover equivalent.

---

## Index/listing 2 — ILANEL Projects

URL: <https://www.ilanel.com/projects>

### 1. Grid

| viewport | columns | tile size | behaviour |
|---|---:|---:|---|
| 1440 | 4 | 345.30px square | fixed grid, equal tiles |
| 1024 | 4 | 243.38px square | fixed grid, equal tiles |
| 390 | 1 | 343.22px square | stacked |

All 51 tiles are identical 1:1 boxes. No featured/larger tile and no masonry. DOM source: `.portfolio-grid-overlay` (`display:grid`) and `.grid-item` (`padding-bottom` equal to its width).

### 2. Tile anatomy

- Image: 1:1 viewport, `object-fit:cover`, focal point from each Squarespace image record (usually 50% 50%, but not globally fixed).
- Resting state: grayscale.
- Text: title only. No location, year, excerpt, category, or metadata.
- Desktop title position: bottom-left inside a full-tile overlay with 24.16px padding at 1440; 17.03px at 1024.
- Desktop title at 1440: Helvetica Neue, 12.544px / 20.0704px, weight 400, letter-spacing .09408px, uppercase, left aligned.
- At 390 the computed title is 14.4064px / 23.0502px and centred—but the parent overlay stays at opacity 0 because touch has no hover. Therefore **the live mobile listing effectively shows images with no project names**. This is visible in the mobile capture and is not an inference.

### 3. Gaps

- Grid gap is exactly 10px both axes at 1440, 1024, and 390.
- Grid outer padding: 14.4px at 1440; 10.24px at 1024; 3.9px top/bottom and 23.4px left/right at 390.
- DOM selector: `.portfolio-grid-overlay`.

### 4. Filtering, sorting, pagination

- No filters, sorting, or pagination.
- All 51 items are present in the initial DOM/load.

### 5. Hover

Hover makes three changes; there is no zoom:

1. `.grid-image` switches `filter:grayscale(1)` to `grayscale(0)`. No transition duration is defined, so computed duration is 0s (instant).
2. `.portfolio-overlay` fades opacity 0 → 1 in .2s, default `ease`. The project-specific override supplies `background:linear-gradient(45deg,#fff,rgba(0,0,0,0))`.
3. `.portfolio-text` fades opacity 0 → 1 in .2s, default `ease`.

Non-obvious source rule (ILANEL `custom.css`):

`section[data-section-id="64a4d1c7a93eb217e9d49e9b"] .grid-item .grid-image{filter:grayscale(1)}` and its `:hover` rule with `grayscale(0)`; the same section's `.portfolio-overlay` defines the white-to-transparent gradient.

---

## Index/listing 3 — ILANEL Light Art

URL: <https://www.ilanel.com/light-art>

### 1. Grid

This is **not a grid**. It is a one-column sequence at all three widths:

- an introductory quotation band;
- 12 full-bleed project/exhibition bands;
- one CTA per band (`EXPLORE`).

At a 900px viewport height, each feature band is 594px (66vh) at both 1440 and 1024 widths. At the 664px mobile viewport it is 438.23px (66vh). Background media uses full-bleed cover cropping. There are no differently sized featured tiles within the sequence, although the first item uses a larger heading treatment.

### 2. Tile/band anatomy

- Image or video fills the band edge-to-edge; exact source ratio and focal point vary by item, so a universal media ratio is **UNKNOWN** and should not be fabricated. The viewport window itself is width × 66vh.
- Title/description and an outlined `EXPLORE` button are centred over the media.
- First project heading: Helvetica Neue, 33.28px / 41.5334px, weight 400, letter-spacing .6656px, uppercase, centred. Mobile: 23.968px / 29.9121px.
- Most subsequent headings: 16px / 20.8px, weight 400, letter-spacing .32px, uppercase, centred. Some use paragraph styling at 16px / 24px.
- CTA at 1440: 11.68px, letter-spacing either .584px or 1.168px depending Squarespace button size, uppercase; padding is 11.68px vertically and 23.36px or 35.04px horizontally.
- No location/year/category field is separated from the title; those facts are written into the editorial title when present.

### 3. Gaps

- A dedicated empty band separates every feature: 90px (10vh) at 1440/1024 with the 900px-high test viewport; 66.39px (10vh) at 390 × 664.
- Horizontal gap: 0; features are full bleed.
- DOM evidence: alternating `.page-section.section-height--medium` and `.page-section.section-height--custom`; the custom sections expose `customSectionHeight:10` in `data-current-styles`.

### 4. Filtering, sorting, pagination

- No filters, sorting, or pagination.
- All 12 linked feature bands load initially.

### 5. Hover

The media and title do not zoom or move. The outlined CTA inverts:

- before: transparent background, `rgb(219,216,209)` text and 1px border;
- after: `rgb(219,216,209)` background, black text;
- transitions: `background-color .1s linear, color .1s linear`.

---

## Single item 1 — Ross Gardam, Orrong by Studio Cobe

URL: <https://www.rossgardam.com.au/article/orrong-by-studio-cobe/>

### 6. Section order and share of page height

Desktop document height: 8504px.

1. Breadcrumb/title/hero/metadata intro: 1141.53px, ~13.4%.
2. Eight alternating editorial rows: 5842.8px, ~68.7%.
3. `Discover More` related carousel: 988.7px, ~11.6%.
4. Footer: 530.77px, ~6.2%.

At 390px the page is 6964px; intro 496.75px (~7.1%), editorial 4860.52px (~69.8%), related carousel 714.44px (~10.3%), footer 891.97px (~12.8%).

### 7. Hero

- Contained, not full viewport: 1240 × 663.59px inside a 100px-side shell at 1440.
- Ratio 1.869:1, matching its 1635 × 875 source.
- `object-fit:cover`, centred.
- Page title is **above**, not over, the image: 28px / 32.2px, weight 400, with 40px bottom margin.
- Mobile hero: 330 × 176.59px; title 24px / 27.6px.
- Source: `.intro .intro__inner .featured__image{...object-fit:cover}` in `rg-styles/header.min.css`; DOM selector `.featured__image`.

### 8. Body copy

The desktop story alternates copy and image across asymmetric two-column rows rather than using a single long text column.

- Reversed rows: 35.5% text / 64.5% image. Source: `.article--alt.article--reversed .article__col` and `.article__col--alt` in `rg-styles/header.min.css`.
- Other rows: 40.5% / 59.5%. Source: `.article--alt .article__col` and `.article__col--alt`.
- CSS copy cap: `.article--alt .article__content{max-width:58rem}`.
- Actual first text line box at 1440: 400.19px; non-reversed copy reaches 540px because of the containing column/padding.
- Body type: 18px / 24.3px, weight 300, left aligned. The base bundle defines `.article .article__content p{font-size:1.8rem;font-weight:300;line-height:1.35}`.
- Mobile rows stack to 100%. Copy box is 320px. The live cascade produces 18px / 24.3px in reversed rows and 14px / 18.9px in alternating non-reversed rows; this inconsistency is present in the rendered page.

### 9. Gallery

- Six editorial images distributed through eight rows.
- One image and one copy block per row, alternating left/right. Images are not paired with another image and are never full bleed.
- Landscape and portrait source ratios are preserved (`height:auto`); the first story image is 799.77 × 532.42px, the next portrait is 502.17 × 754.03px.
- At mobile, image and text stack; reversed rows reorder the image before copy.
- No slideshow in the main story.

### 10. Metadata

Immediately under the hero, an uppercase inline list contains:

- title;
- `Projects - March 2026`;
- `ARCHITECT Studio Cobe`;
- `PHOTOGRAPHY Jack Lovel`.

The block is `.intro__list`, directly below the hero with 4px top padding. A later copy block lists products used and image numbers, followed by architecture/interior-design and photography credits.

### 11. Bottom

`Discover More` appears on a contrasting `rgb(236,233,233)` band. It is a nine-item Flickity carousel, two related cards visible at desktop. There is no explicit back-to-Journal or previous/next link before the global footer.

---

## Single item 2 — ILANEL, SS&A Albury

URL: <https://www.ilanel.com/projects/ssaalbury>

### 6. Section order and share of page height

Desktop document height: 2203px.

1. Split copy/slideshow composition: 1466.8px, ~66.6%.
2. `Related Products` heading: 90px, ~4.1%.
3. Empty related-gallery section: 101px, ~4.6%.
4. Next-item pagination region: ~119px, ~5.4%.
5. Footer: 426.3px, ~19.4%.

At 390px the page is 3180px. The copy/gallery group is 1923.19px (~60.5%): copy 1532.19px followed by a 391px gallery. Footer expansion accounts for 992.16px (~31.2%).

### 7. Hero

There is **no hero**. The page opens directly into the editorial split:

- desktop rendered widths: 504px copy (35%) and 936px slideshow (65%);
- both columns are equal-height at 1466.8px;
- title is in the left copy column, not on an image;
- at mobile, copy stacks above the gallery.

### 8. Body copy

- Desktop copy line box: 356.41px at 1440; 253.44px at 1024.
- Mobile: 343.22px, centred.
- Type: Helvetica Neue, 16px / 24px, weight 400, letter-spacing .8px.
- Title: 16px / 20.8px, weight 400, letter-spacing .32px, uppercase, `rgb(148,110,0)`. Desktop left aligned; mobile centred.
- Desktop copy begins at x=73.8px, providing deliberate whitespace inside the 504px column.

### 9. Gallery

- One seven-slide Squarespace slideshow to the right of copy.
- Desktop gallery viewport: 936 × 703.8px with a 907.22 × 675.02px wrapper; its configured height is 75vh.
- Portrait active image: 450.03 × 675px, centred, `object-fit:contain`—it does not crop to fill the wide column.
- Side arrows; no thumbnails; no captions; autoplay is false. DOM source: `.gallery-slideshow[data-props]` (`autoplayEnabled:false`, `galleryHeight:75`, `thumbnailsEnabled:false`).
- Mobile gallery is a 390px section with a 343.22px-square wrapper; active portrait is 228.67 × 343px, still contained.
- No image grid and no side-by-side image pair.

### 10. Metadata

No separate client/location/year/products metadata block. All available context is embedded in the heading and prose. This page is editorial, but not metadata-rich.

### 11. Bottom

- `Related Products` is present as a heading, but the live `.gallery-grid` contains **zero `.gallery-grid-item` elements**. This is a genuine empty section in the running site.
- Below it is a single next-item link to `Kinetic Landscape | 628 Bourke Street`, with a right arrow.
- There is no previous link and no back-to-Projects link.

---

## Single item 3 — ILANEL, Form & Phenomenon

URL: <https://www.ilanel.com/light-art/formandphenomenon>

### 6. Section order and share of page height

Desktop document height: 4741px.

1. Header spacer: 102.95px, ~2.2%.
2. Hero: 720px, ~15.2%.
3. Intro, exhibition metadata, and three artwork stories: 2539px, ~53.6%.
4. Gallery reel: 676px, ~14.3%.
5. Back-to-Light-Art band: 157.47px, ~3.3%.
6. Next-item pagination: 119.38px, ~2.5%.
7. Footer: 426.3px, ~9.0%.

At 390px the page is 6594px. Hero is 531.19px (~8.1%), the stacked editorial section is 4231.78px (~64.2%), reel is 499px (~7.6%), back band 174.25px (~2.6%), pagination 67.38px (~1.0%), and footer 992.16px (~15.1%).

### 7. Hero

- Full bleed, but not full viewport: exactly 80vh (720px at the 900px desktop viewport; 531.19px at the 664px mobile viewport).
- Media uses `object-fit:cover` and fills the band.
- Title is centred **over** the image: 33.28px / 41.5334px at 1440, 23.968px / 29.9121px at 390; weight 400, uppercase.

### 8. Body copy

- Intro is a single wide column, 1128.97px element width at 1440. Range measurement found real lines up to 1113.23px; this is unusually wide, not a narrow editorial measure.
- At 1024 the column is 802.81px. At 390 it is 343.22px and centred.
- All body copy is 16px / 24px, weight 400, letter-spacing .8px.
- Artwork sections use floated 50/50 compositions: image left/text right, then image right/text left, then image left/text right. The first desktop image is 547.48 × 364.98px, and its adjacent text line fragments top out at ~540.7px.
- On mobile, each image and its copy stack and centre.

### 9. Gallery

The page uses two gallery modes:

1. Three inline artwork images alternated with copy in the editorial section. They are never paired image-to-image.
2. A 19-image horizontal Gallery Reel near the bottom. `.gallery-reel` is 75vh with side controls and no captions. At 1440, the active figure is 969 × 646px in a 676px section, `object-fit:cover`; adjacent reel content can peek at the edge. At mobile the reel section is 499px.

### 10. Metadata

The intro includes a compact textual metadata sequence:

- exhibition dates: `14 May – 14 June 2026`;
- venue: `Goldstone Gallery`;
- address: `41 Derby Street, Collingwood Victoria 3066`.

Each of the three artwork descriptions then includes its own metadata in bold text within the prose flow: edition/series, materials, dimensions, and technology. It is not a card or table; it shares the 16px / 24px paragraph rhythm.

### 11. Bottom

Order is explicit:

1. outlined `BACK TO LIGHT ART` button;
2. `HOME` button;
3. next-item pagination to `Now, You'll See / Goldstone Gallery`;
4. global footer.

There is no previous-item control.

---

## 12. Five concrete devices that make the pages feel considered

1. **RG uses an asymmetric editorial column system, not a generic 50/50 split.** Reversed story rows are 35.5/64.5; the other rows are 40.5/59.5. Copy is capped at 580px and images keep their natural landscape/portrait ratios. That gives every scroll step a deliberate change in mass.

2. **RG separates hierarchy by ratio as well as type.** The Journal feature is a large 5:3 background crop, while ordinary cards use the consistent 1.868:1 editorial crop. A 125px per-card row rhythm and 37.5px image-to-copy gap keep the dense three-column area from feeling like a commodity product grid.

3. **ILANEL Projects is disciplined to one small token: 10px.** The 51-tile grid uses square crops, a 10px gap on both axes, and only 14.4px outer padding at 1440. The grayscale-to-colour switch plus .2s caption fade supplies hierarchy without changing geometry. This is visually strong, though the hover-only caption is an accessibility/usability failure on mobile.

4. **ILANEL Light Art turns spacing into a repeatable cinematic cadence.** Every exhibition window is 66vh and every separator is 10vh. With the measured 900px desktop viewport that is an exact 594px image band followed by 90px of silence, repeated twelve times. The design reads as an exhibition sequence rather than a card list.

5. **Form & Phenomenon combines three scales of storytelling.** It moves from an 80vh full-bleed title hero, to ~547 × 365px alternating image/text studies with per-artwork materials/dimensions/technology, then to a 75vh, 19-image reel. Those explicit scale changes—80vh → half-width studies → 75vh reel—are what keep it from becoming a stack of equal images.

## Plain assessment

- RG's project presentation is the more consistently editorial single-item system: strong metadata, asymmetric alternating rows, product references, and a related-content exit. Its weakness is discoverability because projects have no dedicated index.
- ILANEL Projects has a strong high-density index but a comparatively plain item template. SS&A's split copy/slideshow is deliberate, yet its live Related Products section is empty and mobile index captions are unavailable.
- ILANEL Light Art is the most exhibition-like index and the richest item page. The 66vh/10vh listing rhythm and the hero/editorial/reel scale changes are the most transferable ideas for a redesigned POC.
