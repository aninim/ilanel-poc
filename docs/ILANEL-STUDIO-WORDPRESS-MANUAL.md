# ILANEL STUDIO website update manual

**WordPress + WooCommerce**  
**Draft for Oren review — 20 August 2026**

This is the day-to-day guide for the ILANEL STUDIO team. It covers:

- changing product pictures;
- changing product prices;
- adding a product; and
- adding or editing a News article.

The studio will use two controlled update paths:

1. **WordPress admin** for routine products, prices, pictures, and News.
2. **ILANEL Studio Assistant** for specific approved tasks that the normal
   WordPress interface does not expose. The assistant will be implemented as
   a dedicated agent + skill + authenticated MCP, but the studio will only
   need to describe the task in plain language.

Store payments, tax, shipping, menus, plugins, theme code, redirects, and
site-wide design stay with Oren/site administration unless a separate,
explicitly approved assistant workflow is built for one of them.

> **Admin address during the build:** `https://ilanel.dads42.com/wp-admin/`  
> **Admin address after launch:** `https://ilanel.com/wp-admin/`

Each team member should use their own account. Never share passwords by email
or messaging.

---

## The quick version

### Change a product price

1. Go to **Products → All Products**.
2. Search for the product and select **Edit**.
3. For a simple product, open **Product data → General** and change
   **Regular price**.
4. For a variable product, open **Product data → Variations**, open each
   variation, and change its **Regular price**.
5. Select **Update**, then **View product** and check the result.

Enter the number only: `3195.50`, not `$3,195.50`. Prices are in AUD and the
store is configured to display them with GST.

### Change a product's catalogue picture

1. Go to **Products → All Products** and edit the product.
2. Find **Product image** in the editor sidebar.
3. Select the current picture, choose **Replace**, upload or select the new
   image, and add useful **Alt text**.
4. Select **Update**, then check the product in the catalogue and on mobile.

> **Current image limitation:** Product image changes the catalogue/card
> image and acts as a fallback on new products. Existing product hero
> carousels, story pictures, and finish swatches use custom imported image
> sets. The normal WooCommerce **Product gallery** box does not currently
> control those areas. These changes will be handled through the dedicated
> **ILANEL Studio Assistant**. Until that assistant is live, send them to
> Oren/site administration.

### Add a simple product

1. Go to **Products → Add New Product**.
2. Add the name, short description, price, SKU, Range, product image, and the
   ILANEL specification fields listed in this manual.
3. Keep it as a **Draft** and preview it.
4. Complete the product checklist below.
5. Publish only after a second person checks the preview.

### Add News

1. Go to **Posts → Add New Post**.
2. Add the headline and article text.
3. Add the main picture under **Featured image** in the Post settings
   sidebar.
4. Select **Preview** and check the article.
5. Select **Publish**, confirm, and check both the article and `/news/`.

---

## Before every update

Use this five-step routine:

1. Start with the approved copy, price, and image files.
2. Make the change in WordPress.
3. Preview before publishing whenever WordPress offers a preview.
4. Select **Update** or **Publish**.
5. Open the public page and check it on desktop and phone.

### Choose the correct update path

| Task | Use |
|---|---|
| Product copy, simple/variation prices, SKU, specification fields, product-card image | WordPress admin |
| New simple product Draft | WordPress admin |
| News headline, text, date, and Featured image | WordPress admin |
| Product hero carousel, story images, finish swatches | ILANEL Studio Assistant |
| Supporting News story gallery | ILANEL Studio Assistant |
| Custom metadata or a guided task not exposed by WP | ILANEL Studio Assistant, but only after that task is approved and enabled |
| Payments, tax, shipping, plugins, theme, domain, redirects | Oren/site administration |

The Studio Assistant is task-specific, not a general website administrator.
It should show what it plans to change and request confirmation before any
write. Until it is launched, its tasks temporarily go to Oren/site
administration.

### Golden rules

- Do not change an existing **URL, slug, or permalink**. Old ILANEL URLs are
  being preserved for search engines and existing links.
- Do not delete a product, Range, News post, or image from the Media Library.
  Use **Draft** or ask the site administrator to retire it safely.
- Do not change product type, variation names, tax, shipping, payments,
  plugins, theme files, or menus without approval.
- Do not copy text directly from a formatted PDF or website without checking
  the preview; hidden formatting can come with it.
- If anything looks wrong, stop before publishing and save a Draft.

---

## Preparing pictures

Prepare images before uploading them. WordPress can crop and rotate an image,
but the clean source file should remain in the studio's asset library.

| Use | Maximum width | Target file size | Format |
|---|---:|---:|---|
| Product / shop | 1500 px | under 300 KB | WebP or JPEG |
| Editorial / gallery | 2000 px | under 400 KB | WebP or JPEG |
| Wide hero / banner | 2500 px | under 500 KB | WebP or JPEG |
| Social share | 1200 × 630 px | under 200 KB | JPEG |

Use WebP where possible. JPEG is also fine for photographs. Avoid PNG for
photographs and never upload TIFF, RAW, BMP, or a camera-original file.

Use clear filenames before upload:

- Good: `ilanel-comet-linear-brushed-brass.jpg`
- Avoid: `IMG_4827.jpg` or `FINAL-new-v6.jpg`

Add natural Alt text that says what is in the picture, for example:
`ILANEL Comet linear pendant in brushed brass above a dining table`.
Do not stuff keywords into Alt text.

---

## 1. Change product pictures

### Replace the catalogue/card picture

1. In the dashboard, go to **Products → All Products**.
2. Search by product name.
3. Select **Edit** under the correct product.
4. In the editor sidebar, open **Product image**.
5. Select **Replace** or remove the existing picture and choose
   **Set product image**.
6. Upload the prepared file or choose it from the Media Library.
7. Fill in **Alt text** and select **Set product image**.
8. Select **Update**.
9. Select **View product** and also check the relevant Range page.

This picture is used on product cards and as the fallback hero for a product
without a custom hero gallery.

### Replace a variation picture

Use this only when the product has selectable options such as Size, Colour,
Glass, or Finish.

1. Edit the product.
2. Open **Product data → Variations**.
3. Open the exact variation.
4. Select its image thumbnail and choose the approved picture.
5. Select **Save changes** inside Variations.
6. Select **Update** for the product.
7. On the public page, select that exact option combination and confirm that
   both its picture and price change correctly.

### Hero, story, and finish-swatch pictures

These will be managed through the **ILANEL Studio Assistant**. Give it:

- the product name and public page link;
- the prepared image files;
- which image should be first in the hero;
- the required order of the remaining images; and
- which finish or variation each swatch picture belongs to.

The assistant should return a preview of the proposed order and mappings. The
studio confirms that preview before it writes the changes. Until this task is
enabled in the assistant, send the same request package to Oren/site
administration.

Do not add pictures to the standard **Product gallery** and assume they will
appear in the ILANEL hero. The current custom theme does not read that field.

---

## 2. Change product prices

First identify whether the product is **Simple** or **Variable** in the
Product data box.

### Simple product: one price

1. Go to **Products → All Products** and edit the product.
2. Open **Product data → General**.
3. Enter the approved amount in **Regular price**.
4. Use **Sale price** only for a real approved promotion. If used, select
   **Schedule** and set both a start and end date.
5. Select **Update**.
6. Check the product page, its Range page, and any “Discover More” card that
   shows the product.

### Variable product: a price for every option combination

1. Edit the product and open **Product data → Variations**.
2. Open each variation one at a time.
3. Confirm its option labels, SKU, image, and **Regular price**.
4. Repeat until every available variation has an approved price.
5. Select **Save changes** inside Variations.
6. Select **Update** for the product.
7. Test several combinations on the public product page. Confirm the price
   and image update when the selections change.

A variation without a price may disappear from the shop. Do not edit the
parent product's price and assume all variations will inherit it.

### Replacing one of the provisional prices

Some migrated products carry a provisional `$3,450 AUD` price and a
`TBC-...` SKU. When replacing one:

1. update both the approved price and real SKU;
2. check the public product page; and
3. ask the **ILANEL Studio Assistant** to mark that product's provisional
   price as resolved. Until this task is enabled, tell Oren/site
   administration so the internal marker can be cleared.

---

## 3. Add a product

### Decide the product type first

- Use **Simple product** when there is one purchasable version and one price.
- Use **Variable product** when customers choose between priced combinations
  such as Size × Colour × Glass.

The studio can create a simple product directly. A variable product should
remain a Draft until the site administrator checks its attributes,
variations, image matching, and price range.

### New simple product: step by step

1. Go to **Products → Add New Product**.
2. Enter the approved **Product name**.
3. Leave the permalink alone after WordPress creates it. If this replaces an
   older page or must use a specific URL, stop and ask the site administrator.
4. Add the concise customer-facing copy under **Product short description**.
   This is the main product story shown by the ILANEL template.
5. Add the longer source copy in the main Description editor if supplied.
   The custom product template currently gives the short description visual
   priority.
6. In **Product data**, choose **Simple product**.
7. Under **General**, enter the approved **Regular price**.
8. Under **Inventory**, add the real **SKU** and confirm the stock/backorder
   setting agreed for this made-to-order product.
9. In the same Inventory area, complete the ILANEL fields:

   - **Spec sheet URL** — public HTTPS link to the PDF;
   - **Finishes** — one finish per line;
   - **Lead time** — normally `4–12 weeks`, unless approved otherwise;
   - **Made in** — normally `Melbourne, Australia`;
   - **Type label** — for example `Linear Pendant`;
   - **3D model (.glb)** — only when a tested public model URL exists; and
   - **3D model (.usdz)** — optional iPhone/iPad AR file.

10. In **Ranges**, select exactly one approved primary Range: Pendants, Wall
    Lights, Chandeliers, Lamps, or Editions.
11. Set the **Product image**.
12. Keep **Catalog visibility** public unless there is a reason to hide it.
13. Select **Save Draft**, then **Preview**.
14. Complete the checklist below and ask a second person to review it.
15. Select **Publish** only when it is ready for customers.

### New product checklist

- [ ] Name and Type label are correct.
- [ ] URL has been checked and will not replace an existing live URL.
- [ ] Short description is approved.
- [ ] Real SKU is present and does not start with `TBC-`.
- [ ] Price is approved and shown in AUD.
- [ ] Correct Range is selected.
- [ ] Product image is sharp on desktop and phone.
- [ ] Image Alt text is complete.
- [ ] Finishes, lead time, and “Made in” are accurate.
- [ ] Spec sheet link opens the correct PDF.
- [ ] Stock/backorder behavior is correct.
- [ ] If variable, every valid variation has a SKU, price, and correct image.
- [ ] Hero/story image set has been supplied to the ILANEL Studio Assistant
      if more than one product picture is required.

---

## 4. Add or edit News

The site uses normal WordPress Posts for News. Their public addresses appear
under `/news/` automatically.

### Add a News article

1. Go to **Posts → Add New Post**.
2. Enter the approved headline.
3. Add the article using normal **Paragraph** blocks. Use short paragraphs
   and clear subheadings for long articles.
4. In the Post settings sidebar, open **Featured image** and set the main
   picture. This image appears in the News listing and at the top of the
   article.
5. Add useful Alt text to the image.
6. Check the publication date. Use **Publish immediately** unless the article
   has an approved future date.
7. Select **Preview** and check the headline, date, picture, spacing, and all
   links.
8. Select **Publish**, then confirm.
9. Check both the article and the public `/news/` page. The newest article
   should appear in the large **Latest** position.

### Edit an existing News article

1. Go to **Posts → All Posts**.
2. Search for the headline and select **Edit**.
3. Change the text or replace the **Featured image**.
4. Do not change the permalink.
5. Select **Update** and check both the article and `/news/`.

### Supporting pictures inside News

The Featured image is fully editable in WordPress. The existing alternating
story galleries were imported into a custom image field that the standard WP
interface does not expose. If an article needs several supporting pictures,
save it as a Draft and ask the **ILANEL Studio Assistant** to attach and order
the News gallery. Until that task is enabled, send the ordered image set to
Oren/site administration.

---

## Final check after publishing

### Product

- Correct name, type, price, and Range.
- Correct first image and no stretched or blank pictures.
- Every variation updates price and picture correctly.
- Lead time, finishes, origin, and specification link are correct.
- Page works on desktop and phone.

### News

- Correct headline and date.
- Featured image appears on `/news/` and on the article.
- Excerpt on `/news/` is clean and does not begin with formatting debris.
- Links open the intended pages.
- Article works on desktop and phone.

---

## If something goes wrong

### “I updated it, but the old version still appears”

1. Confirm you selected **Update** or **Publish**.
2. Open the page in a private/incognito window.
3. Refresh once.
4. If it is still old, send the site administrator the page link, what you
   changed, and the time you changed it. The site has more than one cache
   layer and may need an administrator purge.

### “I changed Product image, but the old hero remains”

This is the current custom-gallery limitation. Product image changes the
catalogue/card image, while an existing product hero may still be reading its
imported hero image set. Send the image and required order to the site
administrator.

### “A variation is missing”

Check that the variation is enabled, has a Regular price, and has a valid
option combination. If attributes were renamed or removed, stop and ask the
site administrator to review the variation structure.

### “The product is not visible in the catalogue”

Check that it is Published, its Catalog visibility is public, and the correct
Range is selected. If all three are correct, ask the site administrator.

---

## Use the Studio Assistant or contact Oren?

Use the **ILANEL Studio Assistant** for approved content tasks that WordPress
does not expose:

- product hero carousels and story image sets;
- finish-swatch image mappings;
- supporting News story galleries;
- clearing the internal provisional-price marker after the real price and SKU
  have been entered; and
- any later task that is explicitly added to the assistant's approved skill.

The assistant must show a proposed change, identify the affected product or
News URL, and ask for confirmation before writing.

Send these platform or structural changes to **Oren/site administration**:

- Existing URL, slug, or permalink changes and redirects.
- Complex new variable products or changes to their attributes.
- Removing or restructuring a Range.
- Deleting or retiring a product or News article.
- Menus, homepage layout, footer, forms, SEO templates, or site-wide design.
- Checkout, payments, tax, shipping, email delivery, users, plugins, theme,
  updates, backups, security, or domain settings.

For either route, include the public page link, the exact requested change,
approved copy/price, prepared image files, required image order, and the
desired deadline.

---

## Official help

- [WooCommerce: add a product](https://woocommerce.com/document/managing-products/add-product/)
- [WooCommerce: manage products](https://woocommerce.com/document/managing-products/)
- [WooCommerce: product images and galleries](https://woocommerce.com/document/adding-product-images-and-galleries/)
- [WooCommerce: variable products](https://woocommerce.com/document/variable-product/)
- [WordPress: Post settings and Featured image](https://wordpress.org/documentation/article/page-post-settings-sidebar/)
- [WordPress: Image block and Alt text](https://wordpress.org/documentation/article/image-block/)

---

## Handover note for Oren / the site administrator

Before this manual is treated as final studio training material:

1. build the dedicated ILANEL Studio Assistant as an agent + task skill +
   authenticated, least-privilege MCP over the site's custom content fields;
2. begin with narrow operations for `_ilanel_gallery`, `_ilanel_story`,
   `_ilanel_swatches`, the News `_ilanel_project_gallery` field, and clearing
   `_ilanel_price_is_placeholder` after a verified price/SKU update;
3. require lookup → proposed diff/preview → explicit confirmation → write →
   public-page verification, with an audit record for every write;
4. keep URL/slug changes, deletion, payments, tax, shipping, plugins, theme,
   and domain controls outside the MCP's default permissions;
5. confirm the final studio WordPress account role and exact menu labels it
   can see;
6. replace the temporary admin URL with the final production URL after
   cutover;
7. capture screenshots from the final WordPress interface and Studio
   Assistant; and
8. run one supervised exercise in each path: replace a price and publish a
   News Draft in WordPress, then reorder a product hero and attach a News
   gallery through the Studio Assistant.
