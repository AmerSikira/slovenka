# Native compatibility verification — 1.2.0

Verified on 15 October 2026 using disposable localhost WordPress 7.1.3,
WooCommerce 11.2.1, PHP 8.5 and Chromium. No production changes were made.

- Shop, product categories and product searches use WooCommerce's main query,
  standard loop and core templates. Native min/max price, ascending/descending
  price sorting, pagination and extension query exclusions were exercised.
- Archive and single-product hooks execute; all layouts omit the sidebar.
- Simple, variable, grouped and external products use core rendering. A variable
  product was added through its actual variation form; redirect=no kept the
  shopper on the product. Cart, classic checkout, account login, and Cart/Checkout
  blocks rendered in the browser. Search and mobile layout/menu checks passed.
- WordPress shortcodes, a server-rendered dynamic block, content/title/language
  filters, registered menus, split-post pagination, comments and archive
  pagination executed through native APIs.
- Explicit authenticated setup and theme activation preserved existing merchant
  settings and product/media records. Edited shop paragraphs/blocks/shortcodes
  survived migration. Configured legacy English cart/checkout pages did not loop.
- Header/footer rendered with WooCommerce disabled. Inquiry/contact persistence,
  private uploads, nonce/field validation and manufacturer XLSX tools passed.
- PHP/JavaScript syntax, ZIP asset completeness/deduplication and absence of
  product seeds/static product templates/custom catalogue adapter were checked.

Two independent follow-up code reviews found migration-content loss and a legacy
English redirect loop; both were fixed and re-reviewed with no remaining concrete
findings. This verification does not establish compatibility with every possible
third-party extension, gateway or host configuration.

Run instructions are in README.md. Existing sites must perform the explicit
Appearance > Slovenka setup content upgrade after replacing an older export.
WooCommerce's recommended hook integration is documented at:
https://developer.woocommerce.com/docs/theming/theme-development/classic-theme-developer-handbook/
