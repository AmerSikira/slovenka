# MK Slovenka theme 1.1.0

The theme handles presentation and imports 28 Bosnian/English editorial pages. Products live entirely in WooCommerce; photos live in the Media Library. The theme does not contain a product registry, static product detail pages, starter prices, variations or stock, and never imports or updates products during activation or admin requests.

## Install or upgrade

Build the theme with `python3 scripts/build-theme.py`. Upload `dist/slovenka.zip` or `dist/slovenka-1.1.0.zip` under Appearance → Themes. On an existing install choose **Replace current with uploaded**, then clear page/CDN caches. Existing products, photos, product edits, orders and page edits remain in WordPress. Re-importing is unnecessary for existing products.

A separate, optional [product import package](PRODUCT-IMPORT.md) is built with `python3 scripts/build-product-import.py`. It contains 12 starter products (11 published; MIJA remains a draft at 220 KM), 90 variations and 56 product photographs. Install the independent importer plugin and explicitly click **Products → Slovenka import → Import missing products**. It can be deactivated/deleted afterwards. A portable WooCommerce CSV/image archive is also built for standard imports and bulk editing. Neither archive is part of the theme.

## Manage the shop

Edit products under **Products**, including names, descriptions, short descriptions, prices/sale prices, product images/galleries, categories, local/global attributes, variations and stock. Add a new WooCommerce product normally or via CSV; it appears without editing the theme or rebuilding it. Product URLs come from WooCommerce. The catalogue, colour/size/category filters, search, homepage cards and navigation read current WooCommerce data. Deleted/draft/hidden products disappear from the catalogue; no stale static product page remains published in their place.

The custom shop layout is retained. Product details use WooCommerce's native gallery, descriptions, attribute selectors, variation availability and add-to-cart form, styled with the site's fonts and colours. Native WooCommerce handles quantities, shipping, taxes, coupons, gateways, checkout and order confirmation. All templates remain full width without a sidebar. Legacy detail-page links from older theme exports redirect to native products, preserving colour selections when possible.

WooCommerce's configured language controls its labels. The two editorial languages share the live catalogue; product translations use the site's WooCommerce translation workflow rather than static alternate product records. Existing `_slovenka_en` metadata is preserved but does not override later edits to current product fields.

Theme activation imports editorial pages and assigns home/shop/cart/checkout/privacy/terms pages. It initializes BAM once, but does not configure payment gateways, shipping, taxes or mail. Existing page edits remain intact. Old imported product pages are retained as legacy URL records and redirected; new installs never create them. Editorial photography/fonts/map assets remain bundled with the theme; commerce photos are served from Media.

## Tabele mjera and Upiti

**Products → Tabele mjera → Add New** accepts manufacturer XLSX files up to 5 MB. Both size-across-columns and size-down-rows layouts are supported. Invalid replacements preserve existing measurements. PHP ZIP and SimpleXML are required. Select reusable tables under **Products → Edit product → Tabele mjera**. Assigned published tables appear on the native product's **Tabela mjera / Size guide** tab. The optional importer seeds the supplied ALBA/ARIA/COCO tables without overwriting existing data or explicit assignments. Table editing and inquiry records retain their existing types/meta keys.

**Upiti / Inquiries** stores production and contact submissions privately, with nonces, field/type/size checks and rate limiting. PDF/JPG/PNG technical packs up to 10 MB are stored outside the public web root and downloaded through an authenticated nonce-protected admin action. Mail notification uses `mk_inquiry_email` (default `slovenka.dd@gmail.com`); a failed delivery attempt does not discard the saved inquiry. Confirm WordPress mail delivery on the host.

## Verification

```sh
python3 scripts/build-product-import.py
python3 scripts/build-theme.py
python3 scripts/check-theme-package.py
find wordpress -name '*.php' -exec php -l '{}' \;
node --check script.js
node --check wordpress/slovenka/production.js
```

On a disposable localhost WordPress store with WooCommerce/the theme active:

```sh
wp eval-file scripts/check-theme-import.php --path=/path/to/test-wordpress
node scripts/check-theme-storefront.cjs 'http://127.0.0.1:5191/?post_type=product'
```

The storefront check requires Playwright. Import isolation checks prove theme setup leaves products/media unchanged and new/edited WooCommerce records appear automatically. The browser check covers both shop languages, current Media images, native product links and variation form, stock, add-to-cart, checkout and mobile layout.

On a separate **empty** localhost store with the optional importer active:

```sh
wp eval-file scripts/check-catalogue-import.php --path=/path/to/empty-test-wordpress
```

The standard CSV route is separately verified with `scripts/check-product-csv.php`, using WooCommerce’s own column auto-mapping and importer. Pass the generated CSV path after the script name on an empty disposable store with the optional importer active to supply the photo upload prerequisite.

The import check creates the starter catalogue and verifies exact prices, stable SKUs, MIJA's draft state, all media/variations, repeat import without duplicates and preservation of edits. `scripts/check-size-tables.php` and `scripts/check-admin-features.py` cover XLSX/admin/inquiry functionality.

Generated assets, manifests and ZIPs are ignored by Git and reconstructed by the builders. Install the built ZIP, not an unbuilt source checkout.
