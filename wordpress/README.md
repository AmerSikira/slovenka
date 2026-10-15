# MK Slovenka production theme

Build the uploadable ZIP after regenerating the static pages:

```sh
python3 scripts/build-pages.py
python3 scripts/build-theme.py
```

Upload `dist/slovenka.zip` under **Appearance → Themes → Add New → Upload Theme** and activate it. Activate WooCommerce before or after the theme; product import resumes automatically when WooCommerce becomes available. No separate demo-content XML, CSV, database dump, external image host or import plugin is required.

Activation imports all 50 Bosnian and English pages, all 69 displayed photographs into the Media Library, product descriptions, categories, colour/size variations and galleries. Local fonts, flags, map assets and animations ship inside the theme. The importer sets the imported homepage and WooCommerce shop, cart and checkout pages, privacy/terms pages, and uses BAM (KM). Previous WooCommerce page assignments are recorded in `slovenka_previous_store_pages` for rollback. It does not configure or change payment gateways, shipping zones, tax rules, the store's launch visibility, or send customer email.

11 products are published. MIJA is a **draft at 220.00 KM**, without invented photographs, sizes or descriptive copy. Complete those fields before publishing it. HASI trousers retain their old internal ID/URL for existing links and carts, but are displayed as **ICON**, with sizes 36–46. **ICON EXTRA** is a separate product in sizes 48–54. SIGNATURE blazer and trousers are separate products; their galleries reuse the existing outfit photography.

The import runs automatically in batches of up to approximately 12 seconds. Larger installs continue through WP-Cron and subsequent admin requests. Keep WP-Cron enabled or trigger scheduled events using the host's cron service. Admin notices report pending work or errors. Completed records carry stable import IDs; reactivation skips them and preserves later edits. Interrupted products stay drafts until their variations finish, and retries fill missing records without duplicating siblings. Existing unrelated posts and products are left intact.

WooCommerce success, information, error and coupon notices use the theme’s warm surfaces, typography, outlined icons and square action buttons, with responsive layouts and visible keyboard focus.

Production product selection submits the chosen WooCommerce variation to its native form handler. Cart, checkout, coupons, totals, shipping, payments and order confirmation use WooCommerce; `commerce.js` and browser preview orders are never loaded. The static root HTML site remains a browser preview. WooCommerce labels use the configured WordPress/WooCommerce language; the imported editorial and catalogue pages have separate Bosnian and English versions without requiring a multilingual plugin.

Edit imported pages under **Pages**, prices/variations/stock under **Products**, and photographs under **Media**. The shared header, footer and dialogs preserve the supplied website markup in the theme bundle. Product gallery/copy authoring remains in `script.js`; the production adapter reads current WooCommerce prices, published state, size attributes and available colour/size variations. New products and new colour photography require updating the catalogue source and rebuilding the theme. Changing source data does not overwrite a completed production import.

The original site includes sample measurements, availability outside the explicitly supplied ICON ranges, materials/care and retail-policy copy. Review those values along with payment, shipping, tax and store visibility settings before accepting live orders. Contact forms retain their existing mail-app behavior; production inquiries retain their existing local-browser storage behavior. Activation cannot transfer preview carts, preview receipts, inquiry files or other browser-local data.

## Verification

PHP syntax and JavaScript checks:

```sh
find wordpress/slovenka -name '*.php' -exec php -l '{}' \;
node --check script.js
node --check wordpress/slovenka/production.js
```

Against a **disposable localhost** WordPress installation with this theme and WooCommerce active:

```sh
wp eval-file scripts/check-theme-import.php --path=/path/to/test-wordpress
```

The integration check verifies every approved price and variation, ICON size ranges, MIJA's draft status, complete page/image imports, native WooCommerce cart content, reactivation without duplicates, preservation of later page/price edits, recovery of an interrupted variation import, legacy image alias reuse and fresh WebP media uploads. It temporarily changes test records and refuses non-localhost installations.

The ZIP includes only referenced runtime files and their required licenses. Each photograph uses one full-size WebP file shared by HTML, JavaScript and the importer, plus the responsive WebP sizes actually used by the site. Three unused photographs, JPEG duplicates, preview-only commerce markup/CSS/JS and OS metadata are excluded. The required hero PNG is retained. Builds replace the generated assets tree, so obsolete files cannot accumulate. Existing imported HTML is remapped to the retained photo paths; existing Media Library attachments are reused on upgrade.

Check package integrity with `python3 scripts/check-theme-package.py` after building. It verifies every asset reference, complete pages/products, absence of duplicate images and exclusion of preview files.

Generated assets, the data manifest and ZIP are ignored by Git. The builder reconstructs them from the tracked static website and theme PHP/JavaScript sources. Install the ZIP, not an unbuilt checkout of `wordpress/slovenka`.

## Conversion verification

| Source | Production behavior | Verification |
| --- | --- | --- |
| Bilingual static pages and shared shells | Same markup, styles, fonts, galleries, maps and animations with WordPress URLs and lifecycle hooks | Imported homepage and product checked in browser |
| Catalogue and browser cart | WooCommerce products, all colour/size variations, native cart and checkout | ICON EXTRA size 48 reaches native cart at 75 KM; checkout loads |
| Static prices and sample notices | Approved prices in both languages; production uses current WooCommerce prices | All product and variation prices checked |
| No activation importer | Stable page/product/media IDs, background continuation, dependency recovery | Repeated import, edited content/price preservation and missing-variation repair pass |

Verified locally with WordPress 7.1.3, WooCommerce 11.2.1 and PHP 8.5. Syntax checks also pass. The local test store has no payment gateway configured, so no paid order was attempted.
