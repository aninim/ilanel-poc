# Ross Gardam homepage — rendered and measured specification

## Evidence and units

- Markup inventory: `rg-home.html`.
- All CSS values below come from `rg-styles/header.min.css` unless another file is named. `rg-styles/footer.min.css` contains no rules that match the homepage sections/components covered here, so it does not alter these results.
- Root sizing is **not** `62.5%`: the applicable rule is `html { font-size: 10px; }`. Thus every reported `rem` value below uses `1rem = 10px`. The equivalent pixel values in parentheses are arithmetic conversions from that rule, not visual estimates.
- The CSS bundle is minified onto one line, so selectors are quoted rather than line numbers.
- Browser capture succeeded. `rg-home-1440.png` is 1440 × 7796 physical pixels from a 1440 × 900 CSS-pixel viewport at DPR 1. `rg-home-390.png` is 1170 × 17238 physical pixels from a 390 × 844 CSS-pixel mobile viewport at DPR 3, with mobile/touch emulation; its physical width is therefore 390 × 3, not an incorrect viewport.

## 1. Section inventory

The hero is a preceding `<div class="slider slider--hero">`, not a `<section>`. The five direct `<section>` children of `.main-content`, in document order, are:

| # | Exact class list | First heading text | Resolved background |
|---|---|---|---|
| 1 | `section theme-gray` | `Latest Releases` | `#dfdddd`, from `.theme-gray { background-color: #dfdddd; }` |
| 2 | `section section--circles theme-black` | `Explore Collections by their Tone` | `#000000`, from `.theme-black { background-color: #000; }` |
| 3 | `section section--alt` | `About Us` | `#ece9e9`, inherited from `.section { background-color: #ece9e9; }` because there is no `theme-*` class |
| 4 | `section section__featured-editions theme-black` | `Editions` | `#000000`, from `.theme-black` |
| 5 | `section theme-half-gray section--grid catalogues` | `Catalogues` | Desktop is a hard 50/50 split, not one colour: left `#d5d1ce`, right `#b5b2b0`, from `.theme-half-gray { background: linear-gradient(90deg,#d5d1ce 50%,#b5b2b0 0); }`. At the mobile branch it is solid `#b5b2b0`, from `.theme-half-gray { background:#b5b2b0; }`. |

`theme-black` also changes foreground text to `#ffffff`; `theme-gray` sets it to `#000000`.

## 2. Vertical rhythm

### Applicable layout widths

These are all numeric media-query widths that affect elements present in this homepage layout (including its header/footer), in ascending order. Plugin-only rules and rules whose required modifier class is absent from the markup are excluded.

| Width | Applicable change/source |
|---|---|
| `max-width: 374px` | `.shell` horizontal padding becomes `2rem` (20px). |
| `max-width: 767px` | Main mobile branch. The same rules also apply to `screen and (max-width:810px) and (orientation:landscape)`. Sections, typography, shell, slider images/captions, grid stacking, header, and footer change here. |
| `max-width: 810px` landscape only | Companion branch to the 767px mobile rules above; it is not active at 810px portrait. |
| `max-width: 1023px` | Tablet section padding, shell padding, heading scale, slider caption padding, and several component layouts. |
| `max-width: 1199px` | Shell padding, hero headline column, slider controls, and footer columns. |
| `max-width: 1440px` | Hero headline size and catalogue-column content padding/labels. This branch applies at the 1440px capture exactly. |
| `max-width: 1600px` | Footer navigation column widths. |
| `max-width: 1660px` | `.section--circles .section__circles` expands from `66.66%` to `100%` and changes its margin. |

For avoidance of ambiguity: the bundle also contains breakpoints for unrelated WordPress/WooCommerce plugins and absent component modifiers. For example, the `1340px` rule targets `.slider--alt`, but the Latest Releases wrapper is only `.slider`, so it does not apply. Likewise the `min-width:1023.02px` hero rule requires `.hero__size`, which this hero does not have.

### Outer section padding

Values are `padding-top / padding-bottom`.

| Section | Desktop, above 1023px | Tablet, ≤1023px and outside mobile branch | Mobile, ≤767px or landscape ≤810px | Cascade/source |
|---|---:|---:|---:|---|
| Latest Releases | `16.7rem / 14.7rem` (167/147px) | `10rem / 10rem` (100/100px) | `5rem / 5rem` (50/50px) | Base `.section`; the 1023px and mobile `.section` declarations are `!important`. |
| Explore Collections | `15.6rem / 11rem` (156/110px) | `10rem / 10rem` | `5rem / 5rem` | `.section--circles { padding:15.6rem 0 11rem; }`; the responsive `!important` `.section` shorthands override it. |
| About Us | `16.7rem / 12rem` (167/120px) | `10rem / 10rem` | `7rem / 5rem` (70/50px) | Base `.section`, then `.section--alt { padding-bottom:12rem; }`. At mobile, the later `.section--alt { padding:7rem 0 5rem !important; }` wins over the common mobile rule. |
| Editions | `16.7rem / 14.7rem` | `10rem / 10rem` | `5rem / 5rem` | Base/responsive `.section`; `section__featured-editions` has no outer-padding rule. |
| Catalogues | `16.7rem / 10rem` (167/100px) | `10rem / 10rem` | `5rem / 5rem` | On desktop, `.section:last-child { padding-bottom:10rem; }` is more specific than `.section--grid { padding-bottom:8.3rem; }`. At ≤1023px, the common `!important` shorthand wins; at mobile that makes the bottom 5rem, so the non-important mobile `.section:last-child { padding-bottom:12rem; }` does **not** win. |

There are no outer vertical-padding changes at 1199, 1440, 1600, or 1660px.

### Inner rule-to-content padding

Every section also contains `.shell > .section__inner`. Its own top/bottom padding is separate from the outer values above:

| Section | Desktop | ≤1023px (including mobile) | Source/cascade |
|---|---:|---:|---|
| Latest Releases | `3.9rem / 3.9rem` (39/39px) | same | `.section .section__inner { padding:3.9rem 0; }` |
| Explore Collections | `5.9rem / 5.9rem` (59/59px) | `3.9rem / 3.9rem` | `.section--circles .section__inner { padding:5.9rem 0; }`; responsive `.section .section__inner { padding:3.9rem 0 !important; }` wins. |
| About Us | `3.9rem / 3.9rem` | same | Common inner rule; `.section--alt` changes only right padding. |
| Editions | `3.9rem / 3.9rem` | same | Common inner rule. |
| Catalogues | `3.9rem / 0` | `3.9rem / 3.9rem` | On desktop, `.section:last-child .section__inner { padding-bottom:0; }` beats the less-specific grid shorthand. At ≤1023px the common inner shorthand is `!important`, restoring 3.9rem bottom padding. |

Heading-to-following-content spacing is normally `.section h2 { margin-bottom:9.8rem; }` (98px). Catalogues overrides it with `.section--grid h2 { margin-bottom:2.6rem; }` (26px).

## 3. Typography scale

Unless overridden below, `font-family` is `"Favorit Std", sans-serif`, `letter-spacing` is `normal`, and `text-transform` is `none`. The body rule declares `font-weight:300` and later `font-weight:400` in the same declaration block; the later `400` wins.

### Hero headline

The homepage contains an empty `<h1 style="text-align:right"></h1>`, so these are the defined metrics but no headline glyphs render:

| Range | Size | Other properties |
|---|---:|---|
| Above 1440px | `4.9rem` (49px) | family Favorit Std; weight `400`; line-height `1.05`; letter-spacing `normal`; transform `none` |
| ≤1440px | `3.7rem` (37px) | other properties unchanged |
| ≤1023px, including mobile | `3.4rem` (34px) | other properties unchanged |

Sources, in cascade order: `.h1,h1 { font-size:6rem; line-height:1.05; }`, `.h1,.h2,h1,h2 { font-weight:400; font-family:Favorit Std,sans-serif; }`, then the more-specific `.slider--hero .slider__head h1` at 4.9rem, 3.7rem under 1440px, and 3.4rem under 1023px. The global 3rem mobile `h1` rule loses on specificity to the 3.4rem slider rule.

### Section `h2`

| Range | Family | Size | Weight | Line-height | Letter-spacing | Transform |
|---|---|---:|---:|---:|---|---|
| Above 1023px | Favorit Std | `2.8rem` (28px) | `400` | `1.15` | `normal` | `none` |
| ≤1023px | Favorit Std | `2.6rem` (26px) | `400` | `1.15` | `normal` | `none` |
| Mobile branch | Favorit Std | `2.4rem` (24px) | `400` | `1.15` | `normal` | `none` |

Source: `.h2,h2` plus the 1023px and 767px/810px-landscape versions of that selector. No homepage section adds a font override to its `h2`.

### Descriptive `h3` copy

The global scale is `2.4rem` / `2.2rem` / `1.8rem` (desktop / ≤1023px / mobile), `line-height:1.05`, `font-family:Favorit Std`, and global weight `400`. Homepage entry rules reduce the weight to `300`.

| Context | Desktop | ≤1023px | Mobile | Context-specific differences/source |
|---|---|---|---|---|
| Latest slide description | `2.4rem`, 300, `1.05` | `2.4rem`, 300, `1.05` | `1.6rem`, 300, `1.05` | `.slider .slider__slide-content h3`; mobile margin-bottom changes from `1.3rem` to `.5rem`. |
| Explore Collections description | `2.4rem`, 300, `1.05` | `2.2rem`, 300, `1.05` | `1.8rem`, 300, `1.05` | `.section .section__entry h3` and `.section--circles .section__entry h3`. |
| About Us description | `2.4rem`, 300, `1.03` | `2.2rem`, 300, `1.03` | `1.8rem`, 300, `1.03` | `.section--alt .section__inner .section__entry h3`; letter-spacing is `-.015em`. |
| Editions description | `2.4rem`, 300, `1.1` | `2.2rem`, 300, `1.1` | `1.8rem`, 300, `1.1` | `.section .section__images + .section__content h3`. |
| Catalogue descriptions | `2.4rem`, 300, `1.1` | `2.2rem`, 300, `1.1` | `1.8rem`, 300, `1.1` | `.section--grid .section__entry h3`; letter-spacing is `-.017em`. |

All other listed `h3` properties remain: Favorit Std family and `text-transform:none`.

### Body paragraphs

An ordinary paragraph inherits `body`: `font-family:"Favorit Std",sans-serif; font-size:16px; font-weight:400; line-height:1.2; letter-spacing:normal; text-transform:none`. There is no homepage-wide responsive body-size override. Several descriptions are literally `<p>` elements nested inside `<h3>` in `rg-home.html`; those inherit the applicable `h3` metrics above rather than ordinary body metrics.

### Small uppercase labels and links

- `.link`: inherits Favorit Std, `16px`, weight `400`, line-height `1.2`, and `letter-spacing:normal`; it adds `text-transform:uppercase`. At the mobile branch, `.link { font-size:1.2rem; }` (12px). Catalogue links independently confirm the same 1.6rem/1.2rem sizes via `.section .section__entry--catalogue a`.
- About side label, `.section--alt .section__inner span`: Favorit Std, `1.6rem` (16px), weight `400`, line-height `1.2`, `letter-spacing:normal`, uppercase; mobile is `1.2rem` (12px).
- Catalogue side label, `.section--grid .grid .grid__col span`: Favorit Std, `1.4rem` (14px), weight `400`, line-height `1.2`, `letter-spacing:normal`, uppercase; mobile is `1rem` (10px).
- Counter labels, `.counter .counter__labels ul`, are `1.1rem` (11px), Favorit Std, weight `400`, line-height `1.2`, normal tracking. The markup itself supplies uppercase words (`DAYS`, `HOURS`, `MINS`); CSS does not transform them.

The lack of expanded tracking is intentional and verifiable: none of `.link`, the two side-label selectors, or the counter-label selector declares `letter-spacing`, so the body’s computed `normal` value is inherited. The negative tracking rules above apply only to About/Catalogue descriptive `h3` copy.

### Navigation

The closed header seen in the captures has icon controls and a logo, not text navigation. If the overlay menu is opened, `.menu a` defines Favorit Std, `4rem` (40px), weight `400`, line-height inherited as `1.2`, normal tracking, and no text transform. At ≤1023px it becomes `3rem` (30px) with `line-height:1.6`. General in-page links use the `.link` scale above.

## 4. Latest Releases slider

- **Slide count in markup:** seven `.slider__slide` elements, counted in `rg-home.html` under the Latest Releases `.slider`.
- **Visible at desktop:** one slide. `.slider .slider__slide { position:relative; width:100%; }` makes every cell one viewport-track width. Flickity supplies `.flickity-viewport { overflow:hidden; }` and `.flickity-slider { position:absolute; width:100%; height:100%; }`; its runtime inline transforms place cells along that track. This is percentage sizing plus runtime transform positioning, not grid or flex-basis.
- The wrapper does **not** have the CSS modifier class `.slider--alt`, and cells do not have `.slider__slide--3`; therefore the 33.33%/50%/100% rules for that separate variant do not apply despite the JavaScript hook being named `js-slider--alt`.
- **Horizontal gap:** `0`. The applicable `.slider__slide` has 100% width and no horizontal margin/padding, and neither the track nor viewport declares `gap`/`column-gap`; adjacent Flickity cells therefore meet at the 100% boundaries.
- **Image ratio:** `.slider .slider__slide-image { padding-top:55.4%; }` makes height 55.4% of width, i.e. width:height = `100 / 55.4 = 1.805054...:1`. At the mobile branch, `padding-top:80%`, so width:height = `100 / 80 = 1.25:1`. The rendered mobile cell is correspondingly 330 × 264 CSS pixels. No applicable `aspect-ratio` property is used.
- **Image fitting:** `.slider .slider__slide-image img` is absolutely centred at 50%/50%, translated `-50%,-50%`, given min-width/min-height 100% and width 100%, and uses `object-fit:cover`. The extra `font-family:"object-fit:cover"` declaration is a legacy object-fit polyfill hook.

The so-called caption is not beneath the image; it overlays the image at bottom-right:

1. `.slider__slide-image`
2. `.slider__slide-content`
   1. `<h3><p>description</p></h3>`
   2. `<a class="link">product name</a>`

`.slider .slider__slide-content` is `position:absolute; right:0; bottom:0; max-width:58rem; padding:4.7rem 6.3rem; color:#fff; opacity:.8`. Padding becomes `2rem` at ≤1023px and `1rem` on the mobile branch. The description uses the Latest `h3` scale above. The product link uses Favorit Std, 16px/400/1.2, normal tracking, uppercase, becoming 12px on mobile. Per-slide `body__colour--black` or `body__colour--white` classes override the foreground colour to suit the image.

## 5. Grid and container

### Main container

`.shell { margin:0 auto; max-width:183.5rem; padding:0 10rem; }`. Since the bundle applies `* { box-sizing:border-box; }`, the 183.5rem (1835px) maximum includes the padding; the maximum inner content width is therefore `1835 - 2×100 = 1635px`.

| Range | Horizontal padding per side | Source/order |
|---|---:|---|
| Above 1199px | `10rem` (100px) | base `.shell` |
| ≤1199px and >1023px | `5rem` (50px) | 1199px `.shell` |
| ≤1023px and outside mobile branch | `6rem` (60px) | later 1023px `.shell`; the increase from 50px is real |
| Mobile branch | `3rem` (30px) | later 767px/810px-landscape `.shell` |
| ≤374px | `2rem` (20px) | final narrower override |

Thus the 1440px capture has 100px sides and a 1240px content box; the 390px capture has 30px sides and a 330px content box.

### Multi-column gutters

- Catalogues: `.grid { display:flex; }` and `.grid .grid__col--1of2 { max-width:50%; flex:0 0 50%; }`. The column boxes have no CSS `gap`. The apparent gutter comes from `.section--grid .section__content` padding on both sides: `6.3rem` each above 1440px (12.6rem between adjacent content), `4rem` each at ≤1440px (8rem between content at the desktop capture), and `2rem` each at ≤1023px (4rem between content). At the mobile branch the content’s horizontal padding becomes zero, while `.grid__col--catalogue { min-width:100%; flex:0 0 100%; margin-bottom:4rem; }` stacks the columns.
- Editions image pair: `.section .section__images` is flex; each `.section__image` is 50% with `margin:0 -.1rem`. There is no positive gutter; the two adjacent negative margins total a `.2rem` (2px) overlap at the seam. Each image box uses `padding-top:35.1%` of the pair container and `object-fit:cover` on its absolutely positioned image.

## 6. Details an imitator would miss

### Hairlines

- Default section rule: `.section .section__inner { border-top:.1rem solid #000; }` = 1px black.
- Black sections: `.theme-black.section .section__inner { border-top:.1rem solid #fff; }` = 1px white. This applies to Explore Collections and Editions.
- Latest remains the default 1px black because `.theme-gray` only adds a border selector for `.section__body`, which is absent here.
- About remains the default 1px black.
- Catalogues ends at 1px black: although `.section--grid .section__inner` proposes `.2rem solid #636260`, the later and more-specific `.theme-half-gray.section .section__inner { border-top:.1rem solid #000; }` wins.

### Link and hover motion

`.link:after` is a 1px (`.1rem`) current-colour underline with initial `transform:scaleX(0)`, `transform-origin:100% 0`, and `transition:transform .4s`. Because no timing function is supplied, the CSS initial value `ease` applies. Inside `@media (hover:hover) and (pointer:fine)`, `.link:hover:after` changes the transform to `none`; the origin changes to `0 0` through the corresponding entry-link rule, so the line reads as drawing across rather than simply appearing. Menu and footer links use the same 1px/0.4s scale-X pattern.

The homepage also enables a radial pseudo-element behind `.section__entry` via `body.home .section .section__entry:after`. It transitions for `.4s` (default all/ease) from opacity 0 to 1 on hover-capable devices. Theme selectors replace the gradient palette on black and half-gray sections.

Latest captions add a separate hover glow: `.slider .slider__slide-content:before` is translated `-50%,-50%`, starts at opacity 0, and uses `transition:all .4s ease`; `.slider__slide-content:hover:before` changes opacity to 1. There is no homepage image-scale/zoom transform on hover.

### Ratios and transforms

No `aspect-ratio` declaration applies to a homepage element. The only literal `aspect-ratio` properties in the bundle target Plyr video and `.article .image__feature-*`; none of those selectors occur in these five sections. Homepage media ratios instead use percentage top padding: Latest 55.4%/80% and Editions 35.1%, as specified above.

The visible image transforms are centring transforms (`translate(-50%,-50%)`), not hover zooms. The meaningful hover transforms are the underline pseudo-elements changing from `scaleX(0)` to `none`.

## 7. What the screenshots show that the CSS alone does not

The hero occupies the complete first viewport in both captures. In the desktop rendered DOM it runs from y=0 to y=900 in a 900px-high viewport, and Latest Releases begins at y=900; visually, the first viewport is therefore 100% hero and 0% of the next section. The mobile image likewise reaches the 844px viewport boundary before the gray Latest Releases band begins.

There is no visible hero headline to position: the staged/live `<h1>` is empty, and the captures show only the imagery beneath the header. The header is visibly overlaid on the image, with the logo centred and small controls at the edges; the rendered DOM resolves it as fixed with a transparent background.

The hero crop changes much more dramatically than the source image dimensions imply. Desktop shows several vertical brass members and a broad horizontal base; the 390px view centres on one narrow member, with the lower bronze plane occupying much more of the frame. That is the actual visual consequence of centred `object-fit:cover` at these two aspect ratios.

Latest Releases reads as one large editorial image, not a row of product cards. Its description and product label are a compact, translucent overlay in the image’s lower-right region on desktop; on mobile the same overlay covers a substantial part of the smaller image. The screenshots also confirm that the Catalogue half-gray split becomes a single darker gray on mobile and that its two 50% desktop columns stack vertically.
