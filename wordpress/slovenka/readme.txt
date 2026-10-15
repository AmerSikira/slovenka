=== MK Slovenka ===
Version: 1.2.2
License: GPLv2 or later

Classic WordPress theme with native WooCommerce archive queries, product loops,
shared core templates and hooks. No product records or per-product templates.
Full-width layouts; no sidebar. Products and photographs are imported separately.

UPGRADING FROM AN OLDER EXPORT
1. Upload and replace the current theme with this ZIP. Clear page/CDN caches.
2. Open Appearance > Slovenka setup and click Create missing pages / upgrade
   imported content. This explicit operation removes obsolete catalogue markup
   from old imported pages, preserving merchant additions and saving a backup.
3. Leave the page-assignment checkbox off to keep assignments entirely manual.
   Existing valid assignments and currency are never overwritten.
4. Manage products through WooCommerce, pages through WordPress, and navigation
   through Appearance > Menus. Product re-import is unnecessary for existing data.

Activation and ordinary requests never import content or change store settings.
Cart/Checkout support native shortcodes and blocks. WooCommerce controls sorting,
pagination, filtering, stock, variations, cart, checkout and add-to-cart redirects.

Custom size-table and inquiry tools remain available. Product import is a separate
optional plugin or standard WooCommerce CSV, never part of theme activation.

STOREFRONT LANGUAGES
The homepage language is the default for native store routes. The flag switcher
uses explicit lang=bs/en links through the catalogue, search, cart and checkout.
Bosnian WooCommerce translations are bundled; installed language packs take
precedence. Administrator language and merchant product content are unchanged.
See languages/README.md for translation attribution, source and license.

PRODUCT SWATCHES
Colour circles and size buttons are built into the theme, with no swatch plugin.
The enhancement script loads only on variable product pages and uses the existing
WooCommerce variation logic. Native dropdowns work when JavaScript is disabled.
