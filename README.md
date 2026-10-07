# MK Slovenka

Standalone HTML pages with shared CSS and small JavaScript enhancements. No browser framework, package installation or build step is needed to open or host the finished site.

## Open the site

Open `index.html` directly, or serve the folder:

```sh
python3 -m http.server 5187 --bind 127.0.0.1
```

Then visit http://127.0.0.1:5187.

## Pages and navigation

Every destination is a physical document: `index.html`, `shop.html`, `company.html`, `manufacturing.html`, `inquiry.html`, `contact.html`, five `product-*.html` pages and five `support-*.html` pages. Commerce has separate `cart.html`, `checkout.html` and `order.html` pages. English documents use an `-en` suffix, including `index-en.html` and `product-coco-en.html`.

Navigation follows ordinary links and loads the next document. There is no hash router or client-side page replacement. Search and catalogue selections use native query strings such as `shop.html?category=trousers&size=38`; JavaScript enhances the catalogue results, product gallery, variant selections and drawers. Base product content and navigation remain available with JavaScript disabled. Each document declares its language, page title and description. Language links lead to the matching physical page and preserve query parameters and anchors when JavaScript is available.

## Files and authoring

- Root `*.html` files — complete pages ready for static hosting and later WordPress template adaptation.
- `styles.css` — shared responsive design and company green accents.
- `script.js` — catalogue data and interactive enhancements; no routing.
- `motion.js` — optional one-shot GSAP entrances for editorial copy, section headings and cooperation cards, plus scroll-triggered number counters.
- `commerce.css` and `commerce.js` — cart, checkout and received-order presentation and preview behavior.
- `templates/site-source.html` — shared header/footer, page content and dialog authoring source in Bosnian and English.
- `templates/commerce/` — commerce page content fragments in both languages.
- `scripts/build-pages.py` — optional authoring tool that regenerates the physical documents from these sources and catalogue data in `script.js`.
- `assets/img/` — original supplied photographs, preserved.
- `assets/images/` — optimized image variants used by the site.
- `assets/fonts/` — local Space Grotesk fonts and their license.

To regenerate after changing authoring sources, use Python 3 with BeautifulSoup (`beautifulsoup4`) and Node.js:

```sh
python3 scripts/build-pages.py
```

These tools are required only when regenerating files. Hosting and opening the generated website require none of them. Do not edit generated HTML and then regenerate without moving the edits into the source fragments first.

For later WordPress conversion, shared header/footer markup can become `header.php` and `footer.php`; product, shop, cart, checkout and order documents provide the page designs to map onto WooCommerce templates and hooks. The current cart is a local HTML preview, not a WooCommerce installation.

## Preview behavior

Prices, sizes, body measurements, stock and delivery charges are examples. The cart persists in browser storage. Checkout previews do not take payments, send emails, reserve inventory or create an order on a server.

Product pages open the Bosnian body-measurement table from “Tabela mjera” beside the size selector. `BODY_MEASUREMENTS` in `script.js` contains shared sample circumferences for sizes 36–44. Replace them with approved sizing data before live sales; they are not garment dimensions.

Inquiries, contact messages and optional technical attachments are saved only in this browser using IndexedDB. They are not emailed or shared with a team. Clearing site data removes saved records. Form fields remain available if saving fails.

Before live sales, supply approved catalogue data, measurements, materials/care, company contacts, manufacturing scope and retail/privacy policies. Payments, stock management, email delivery and shared inquiry storage require backend integration.

Space Grotesk is licensed under SIL OFL (`assets/fonts/OFL.txt`). Inline icons retain the Lucide license in `assets/icons-LICENSE.txt`.

GSAP core 3.15.0 is pinned and served locally from `assets/vendor/gsap/`, with its Standard No Charge license in `LICENSE.txt`. No CDN request or plugins are needed. [Official installation guidance](https://gsap.com/docs/v3/Installation/) and [matchMedia documentation](https://gsap.com/docs/v3/GSAP/gsap.matchMedia()/) describe the loading and preference handling used here.

Motion uses transforms and `autoAlpha` for short, one-time entrances near the viewport, driven by IntersectionObserver. Hero and product images, controls and layout dimensions are not animated. Editorial copy stays visible before an entrance; cooperation cards are hidden only after the enhancement initializes and reveal individually on scroll. All content stays visible when JavaScript, GSAP or IntersectionObserver is unavailable. Reduced-motion preferences skip motion; changing the preference reverts active tweens and restores final number text. Keyboard focus finishes its containing entrance immediately, and observers/listeners are cleaned up on preference changes and navigation.

The homepage cooperation steps are three bordered cards, arranged in a row on desktop and stacked on mobile. Each card fades and rises into view once, with a short stagger when several enter together. Its badge counts from `00` to `01`, `02` or `03`. The four homepage statistics also count from zero to their final values, retaining language-specific separators and `+` suffixes. Screen readers receive static final statistics, while the changing visual counters are hidden from assistive technology. Inline card animation styles are cleared on completion. The cards are excluded from generic entrances to prevent competing motion.
