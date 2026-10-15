# MK Slovenka theme 1.2.3

The theme handles presentation and offers 23 Bosnian/English starter editorial pages through an explicit setup action. Products live entirely in WooCommerce; photos live in the Media Library. The theme does not contain a product registry, static product detail pages, starter prices, variations or stock, and never imports or updates products during activation or admin requests.

## Install or upgrade

Build the theme with `python3 scripts/build-theme.py`. Upload `dist/slovenka.zip` under Appearance → Themes. On an existing install choose **Replace current with uploaded**, then clear page/CDN caches. Existing products, photos, product edits, orders and page edits remain in WordPress. Re-importing is unnecessary for existing products.

A separate, optional [product import package](PRODUCT-IMPORT.md) is built with `python3 scripts/build-product-import.py`. It contains 12 starter products (11 published; MIJA remains a draft at 220 KM), 90 variations and 56 product photographs. Install the independent importer plugin and explicitly click **Products → Slovenka import → Import missing products**. It can be deactivated/deleted afterwards. A portable WooCommerce CSV/image archive is also built for standard imports and bulk editing. Neither archive is part of the theme.

## Manage the shop

Edit products under **Products**, including names, descriptions, short descriptions, prices/sale prices, product images/galleries, categories, local/global attributes, variations and stock. Add a new WooCommerce product normally or via CSV; it appears without editing the theme or rebuilding it. Product URLs come from WooCommerce. The shop, category/tag/attribute archives, sorting, pagination, price filters and product search use WooCommerce’s main query, standard loop and extension hooks. Homepage product sections use WooCommerce’s standard products shortcode. On activation, the theme creates and assigns Početna, Trgovina, O nama, Proizvodnja and Kontakt when no valid primary menu is assigned. Existing assigned menus are preserved. On an already active installation, switch to another installed theme and reactivate Slovenka once if a menu is needed. Manage navigation under Appearance → Menus. Trgovina keeps its shop link and opens live product categories on desktop hover or keyboard focus, with a separate toggle on mobile. The standalone Kategorije item is removed from the displayed header, including legacy menu entries; saved menu records and custom child links remain intact. Deleted/draft/hidden products disappear from the catalogue; no stale static product page remains published in their place.

The catalogue uses WooCommerce’s own shared archive and product templates, with theme styling and wrapper hooks. There is no custom catalogue adapter, catch-all woocommerce.php or template override directory. Product details use WooCommerce's native gallery, descriptions, attribute selectors, variation availability and add-to-cart form, styled with the site's fonts and colours. Colour attributes (`colour`, `color`, `boja`, including global `pa_` versions) use solid swatches, and size attributes (`size`, `velicina`) use numbered/text buttons. `inc/swatches.php`, loaded by `functions.php`, configures these controls; the small `product-swatches.js` enhancement loads only on variable product pages and reuses WooCommerce’s existing variation script. Local and global attributes are supported. Known colour shades match the original catalogue; unknown colours remain readable text buttons. Extend shades through the `slovenka_swatch_colors` filter. Without JavaScript, the original dropdowns remain usable. Native WooCommerce handles quantities, shipping, taxes, coupons, gateways, checkout and order confirmation. All templates remain full width without a sidebar. Legacy detail-page links from older theme exports redirect to native products, preserving colour selections when possible.

The storefront defaults to the imported homepage’s language, independently of the administrator’s WordPress language. A `?lang=bs` / `?lang=en` selection follows product, category, search, cart and checkout links. The flag switcher is available on native product and shop pages as well as editorial pages. WooCommerce interface labels use that language; the theme includes the community Bosnian translation as a fallback when no merchant-installed Bosnian WooCommerce pack exists. Administrator, REST and cron locales are unchanged. The two languages share the live catalogue; merchant-authored product descriptions, category names and custom menu labels use the site's normal product/content translation workflow rather than static alternate product records. Existing `_slovenka_en` metadata is preserved but does not override later edits to current product fields.

Only the missing primary navigation menu is created on theme activation. Editorial pages/products/media and store settings are never imported or assigned automatically. After upgrading an older export, open **Appearance → Slovenka setup** and explicitly click **Create missing pages / upgrade imported content**. This migrates old imported catalogue HTML, preserves additional edited text/blocks in storage, and saves a backup of upgraded content. The shop header displays only Trgovina (Shop in English); stored shop introductory content is not rendered. Category headings/descriptions and native product controls remain available. It creates missing starter editorial pages; existing edited pages are not replaced with seed content. Products/media are untouched. The optional unchecked checkbox assigns imported pages only when no valid role is already configured. Currency is always merchant-controlled.

WordPress processes page content through `the_content`, including blocks, shortcodes, password protection and plugin content filters. Titles, language attributes, menus, assets, post pagination and comments use standard WordPress APIs. Cart/checkout pages may use standard WooCommerce shortcodes or Cart/Checkout blocks. The merchant controls redirect-after-add, payment, shipping, taxes, policies, currency and page assignments. Legacy English cart/checkout aliases redirect only when they are not themselves the configured page; endpoint/query data is retained. Editorial photography/fonts/map assets remain bundled; product photos use Media.

## Tabele mjera and Upiti

**Products → Tabele mjera → Add New** accepts manufacturer XLSX files up to 5 MB. Both size-across-columns and size-down-rows layouts are supported. Invalid replacements preserve existing measurements. PHP ZIP and SimpleXML are required. Select reusable tables under **Products → Edit product → Tabele mjera**. Assigned published tables appear on the native product's **Tabela mjera / Size guide** tab. The optional importer seeds the supplied ALBA/ARIA/COCO tables without overwriting existing data or explicit assignments. Table editing and inquiry records retain their existing types/meta keys.

**Upiti / Inquiries** stores production and contact submissions privately, with nonces, field/type/size checks and rate limiting. PDF/JPG/PNG technical packs up to 10 MB are stored outside the public web root and downloaded through an authenticated nonce-protected admin action. Mail notification uses `mk_inquiry_email` (default `slovenka.dd@gmail.com`); a failed delivery attempt does not discard the saved inquiry. Confirm WordPress mail delivery on the host.

## Verification

```sh
python3 scripts/build-product-import.py
python3 scripts/check-product-import-package.py
python3 scripts/build-theme.py
python3 scripts/check-theme-package.py
python3 scripts/check-storefront-language.py --url http://127.0.0.1:5196/
find wordpress -name '*.php' -exec php -l '{}' \;
node --check script.js
node --check wordpress/slovenka/theme.js
```

On a disposable localhost WordPress store with WooCommerce/the theme active:

```sh
wp eval-file scripts/check-theme-import.php --path=/path/to/test-wordpress
wp eval-file scripts/check-menu-activation.php --path=/path/to/test-wordpress
python3 scripts/check-shop-heading.py --wp /path/to/wp-cli.phar --path /path/to/test-wordpress --url http://127.0.0.1:5196
node scripts/check-shop-menu.cjs http://127.0.0.1:5196/
python3 scripts/check-native-compatibility.py --wp /path/to/wp-cli.phar --path /path/to/test-wordpress --url http://127.0.0.1:5196
```

The native compatibility runner requires BeautifulSoup and Playwright, a localhost store with imported starter products, administrator access and mail disabled. It creates and cleans up temporary fixtures. It checks native queries, category/search routes, sorting/price limits/pagination, extension hooks, content filters/dynamic blocks, menus/titles/language, merchant setting preservation, product types, account, classic and block cart/checkout, add-to-cart redirect behavior and responsive layout. Separate setup tests cover preservation of edited shop content. This is tested compatibility, not a guarantee for every third-party extension.

On a separate **empty** localhost store with the optional importer active:

```sh
wp eval-file scripts/check-catalogue-import.php --path=/path/to/empty-test-wordpress
```

The standard CSV route is separately verified with `scripts/check-product-csv.php`, using WooCommerce’s own column auto-mapping and importer. Pass the generated CSV path after the script name on an empty disposable store with the optional importer active to supply the photo upload prerequisite.

The import check creates the starter catalogue and verifies exact prices, stable SKUs, MIJA's draft state, all media/variations, repeat import without duplicates and preservation of edits. `scripts/check-size-tables.php` and `scripts/check-admin-features.py` cover XLSX/admin/inquiry functionality.

Generated assets, manifests and ZIPs are ignored by Git and reconstructed by the builders. Install the built ZIP, not an unbuilt source checkout.
