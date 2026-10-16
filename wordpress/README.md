# MK Slovenka theme 1.3.0

The theme handles presentation and offers 23 Bosnian/English starter editorial pages through an explicit setup action. Products live entirely in WooCommerce; photos live in the Media Library. The theme does not contain a product registry, static product detail pages, starter prices, variations or stock, and never imports or updates products during activation or admin requests.

## Install or upgrade

Build the theme with `python3 scripts/build-theme.py`. Upload `dist/slovenka.zip` under Appearance → Themes. On an existing install choose **Replace current with uploaded**, then clear page/CDN caches. Existing products, photos, product edits, orders and page edits remain in WordPress. Re-importing is unnecessary for existing products.

A separate, optional [product import package](PRODUCT-IMPORT.md) is built with `python3 scripts/build-product-import.py`. It contains 12 starter products (11 published; MIJA remains a draft at 220 KM), 90 variations and 56 product photographs. Install the independent importer plugin and explicitly click **Products → Slovenka import → Import missing products**. It can be deactivated/deleted afterwards. A portable WooCommerce CSV/image archive is also built for standard imports and bulk editing. Neither archive is part of the theme.

## Manage the shop

Edit products under **Products**, including names, descriptions, short descriptions, prices/sale prices, product images/galleries, categories, local/global attributes, variations and stock. Add a new WooCommerce product normally or via CSV; it appears without editing the theme or rebuilding it. Product URLs come from WooCommerce. The shop, category/tag/attribute archives, sorting, pagination, price filters and product search use WooCommerce’s main query, standard loop and extension hooks. Homepage product sections use WooCommerce’s standard products shortcode. On activation, the theme creates and assigns Početna, Trgovina, O nama, Proizvodnja and Kontakt when no valid primary menu is assigned. Existing assigned menus are preserved. On an already active installation, switch to another installed theme and reactivate Slovenka once if a menu is needed. Manage navigation under Appearance → Menus. Trgovina keeps its shop link and opens live product categories on desktop hover or keyboard focus, with a separate toggle on mobile. The standalone Kategorije item is removed from the displayed header, including legacy menu entries; saved menu records and custom child links remain intact. Deleted/draft/hidden products disappear from the catalogue; no stale static product page remains published in their place.

The catalogue uses WooCommerce’s own shared archive and product templates, with theme styling and wrapper hooks. There is no custom catalogue adapter, catch-all woocommerce.php or template override directory. Product details use WooCommerce's native gallery, descriptions, attribute selectors, variation availability and add-to-cart form, styled with the site's fonts and colours. Under **Products → Attributes**, choose the native attribute **Type**: **Boja / Colour**, **Veličina / Size**, or the default text/dropdown. Names can be arbitrary. For colour attributes, open **Configure terms** and add/edit each term’s **Nijansa / Shade** using the WordPress colour picker or a HEX code; the term list also shows the saved shade. Shared colour swatches read term metadata, and size attributes render labelled buttons. A colour term with no shade remains a readable text button rather than a guessed colour.

Existing products with local/per-product attributes can be configured under **Product → Product data → Attributes**: choose **Prikaz / Display**, assign a shade to each value, and click **Save attributes** or update the product. After changing value lists, save attributes to refresh the shade fields. The original imported local attributes retain their previous display/shade defaults until explicitly saved, so upgrading does not change their existing appearance. Explicit settings always take precedence; newly created attributes are not inferred from names. The attribute and term settings remain stored in WooCommerce/WordPress across theme updates.

The single-product presentation follows the approved static ARIA page: gallery/purchase-column proportions, model heading and subtitle from the current product name, original size-button dimensions, full-width bag button and the three green benefit pills. Composition/care, help links and native extension content appear as accordions. The former Kroj i detalji accordion is removed; stored product descriptions remain available to the product introduction. Enter **Sastav i održavanje** under **Products → Add/Edit product → Product data → General**. The multiline field supports basic HTML and appears in the composition/care accordion. An empty field shows the existing contact message; clearing it removes previously saved instructions. The standard description and additional-information headings are omitted inside the panels. Product reviews, ratings and new review submissions are disabled on the storefront; existing review records and admin settings are retained. Native product meta remains below them. `inc/product-design.php` and the small `product-design.js` implement presentation through WooCommerce hooks; variation forms, product records and checkout remain native. The static preview’s “no payment taken” message is not shown on the live storefront. Standard quantity-one controls are visually omitted from this composition; quantity still posts through the native form and can be edited in the cart, while nonstandard minimum quantities remain visible. The first colour opens selected unless a merchant default or explicit URL/form choice is present. Out-of-stock variations use native inactive-option matching for the crossed-out state; backorder availability follows WooCommerce.

`functions.php` loads `inc/swatches.php` and `inc/swatch-admin.php`. The small `product-swatches.js` enhancement loads only on variable product pages and reuses WooCommerce’s existing variation script. The WordPress colour picker and `swatch-admin.js` load only on product editing and relevant term-admin screens. No colour-picker library loads on the storefront. Without JavaScript, original storefront dropdowns remain usable. Native WooCommerce handles quantities, shipping, taxes, coupons, gateways, checkout and order confirmation. All templates remain full width without a sidebar. Legacy detail-page links from older theme exports redirect to native products, preserving colour selections when possible.

The storefront defaults to the imported homepage’s language, independently of the administrator’s WordPress language. Bosnian storefront URLs are unprefixed; English shop, product, category, search, cart, checkout and account URLs use `/en/`. Editorial translations retain their existing separate page permalinks. The flag switcher changes the current route without adding language parameters. Existing `?lang=bs` / `?lang=en` links redirect to their clean equivalent, preserving filters and selections. State-changing links use temporary redirects; POST/AJAX requests are never permanently redirected. The `/en/` prefix is reserved for language routing. Rewrite rules refresh once on upgrade; native admin, REST and asset URLs retain their original paths. The flag switcher is available on native product and shop pages as well as editorial pages. WooCommerce interface labels use that language; the theme includes the community Bosnian PHP catalogue and builds per-script JavaScript catalogues for WooCommerce blocks. Reviewed supplementary storefront strings cover gaps in the community catalogue, including cart/checkout labels, accessibility text and common errors. Installed PHP catalogues take precedence; installed JavaScript translations retain their nonempty entries and receive missing fallback strings. The Store API receives the checkout language for validation messages. Untouched English default privacy notices are translated at display time; merchant-authored notices remain unchanged. Administrator, non-store REST and cron locales are unchanged. The two languages share the live catalogue; merchant-authored product descriptions, category names and custom menu labels use the site's normal product/content translation workflow rather than static alternate product records. Existing `_slovenka_en` metadata is preserved but does not override later edits to current product fields.

Only the missing primary navigation menu is created on theme activation. Editorial pages/products/media and store settings are never imported or assigned automatically. After upgrading an older export, open **Appearance → Slovenka setup** and explicitly click **Create missing pages / upgrade imported content**. This migrates old imported catalogue HTML, preserves additional edited text/blocks in storage, and saves a backup of upgraded content. The shop header displays only Trgovina (Shop in English); stored shop introductory content is not rendered. Category headings/descriptions and native product controls remain available. It creates missing starter editorial pages; existing edited pages are not replaced with seed content. Products/media are untouched. The optional unchecked checkbox assigns imported pages only when no valid role is already configured. Currency is always merchant-controlled.

WordPress processes page content through `the_content`, including blocks, shortcodes, password protection and plugin content filters. Titles, language attributes, menus, assets, post pagination and comments use standard WordPress APIs. Cart/checkout pages may use standard WooCommerce shortcodes or Cart/Checkout blocks. Both share the theme’s contained page layout, typography, green actions and warm order-summary panels, including imported pages carrying legacy source metadata. The merchant controls redirect-after-add, payment, shipping, taxes, policies, currency and page assignments. Legacy English cart/checkout aliases redirect only when they are not themselves the configured page; endpoint/query data is retained. Editorial photography/fonts/map assets remain bundled; product photos use Media.

## SEO and indexing

The theme supplies page descriptions, a single canonical URL, Open Graph and Twitter cards, and reciprocal `bs` / `en` / `x-default` alternate links for published editorial translations. Metadata uses current product copy, page content/excerpts, taxonomy descriptions and media. Homepage titles describe the clothing and manufacturing business; shop titles follow the storefront language. Native semantic templates retain a single main heading, including empty search results and the posts index.

JSON-LD provides Organization, WebSite, WebPage/CollectionPage, breadcrumbs and Article data where appropriate. WooCommerce remains the source for Product/Offer prices, currency and stock; hidden reviews are omitted from its product schema. No ratings, return policies, delivery fees or business identifiers are invented. Set the site name and custom logo in WordPress and maintain product photos/copy to control these values.

Cart, checkout, account, search, preview and filtered URLs are noindex. Shared English product views translate controls, not merchant product copy, so those duplicate views are noindex and are not advertised as authored English product translations. Existing editorial translations remain indexable. Canonicals remove tracking, sort and variation parameters while retaining archive pagination. WordPress core XML sitemaps remain available and omit configured private commerce pages and legacy commerce aliases. WordPress’s search-engine visibility setting remains authoritative.

If Yoast, Rank Math, All in One SEO, SEOPress or The SEO Framework is active, theme SEO output yields to it to avoid duplicate metadata. Other integrations can disable it through `slovenka_seo_enabled`. Descriptions can be customized through `slovenka_seo_description`. Check production robots/caching and submit the sitemap in Search Console after installing; these changes do not guarantee rankings or rich results.

## Tabele mjera and Upiti

**Products → Tabele mjera → Add New** accepts manufacturer XLSX files up to 5 MB. Both size-across-columns and size-down-rows layouts are supported. Invalid replacements preserve existing measurements. PHP ZIP and SimpleXML are required. Select reusable tables under **Products → Edit product → Tabele mjera**. Assigned published tables open from the underlined **Tabela mjera** link beside the size label, using the approved product-page dialog design. The selected size is highlighted when it matches a table row. Products without an assigned table show an availability message, and the same content remains accessible below the product without JavaScript. The optional importer seeds the supplied ALBA/ARIA/COCO tables without overwriting existing data or explicit assignments. Table editing and inquiry records retain their existing types/meta keys.

**Upiti / Inquiries** stores production and contact submissions privately, with nonces, field/type/size checks and rate limiting. PDF/JPG/PNG technical packs up to 10 MB are stored outside the public web root and downloaded through an authenticated nonce-protected admin action. Mail notification uses `mk_inquiry_email` (default `slovenka.dd@gmail.com`); a failed delivery attempt does not discard the saved inquiry. Confirm WordPress mail delivery on the host.

## Verification

Building the theme requires Python `beautifulsoup4` and `polib`, plus Node.js. The builder regenerates JavaScript translation JSON from the tracked PO and `languages/storefront-bs.json`; no translation build dependency is needed on the WordPress host.

```sh
python3 scripts/build-product-import.py
python3 scripts/check-product-import-package.py
python3 scripts/build-theme.py
python3 scripts/check-theme-package.py
python3 scripts/check-storefront-language.py --url http://127.0.0.1:5196/
wp eval-file scripts/check-product-care.php --path=/path/to/test-wordpress
wp eval-file scripts/check-attribute-swatches.php --path=/path/to/test-wordpress
node scripts/check-swatch-admin.cjs /path/to/wp-cli.phar /path/to/test-wordpress http://127.0.0.1:5196
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
node scripts/check-product-design.cjs http://127.0.0.1:5196
python3 scripts/check-seo-language.py --wp /path/to/wp-cli.phar --path /path/to/test-wordpress --url http://127.0.0.1:5196
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
