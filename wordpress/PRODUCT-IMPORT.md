# Separate WooCommerce catalogue

The theme contains no product seeds, product detail pages, prices, sizes or stock. The theme reads current WooCommerce products and Media Library images. Editing or adding products does not require a theme rebuild. Theme activation and updates do not write products.

## Recommended: one manual import

1. Replace the theme with `slovenka-1.2.0.zip` in Appearance → Themes. Existing products remain in WooCommerce. Clear page/CDN caches.
2. Install `slovenka-product-import.zip` under Plugins → Add New → Upload Plugin, and activate it alongside WooCommerce.
3. Open **Products → Slovenka import** and click **Import missing products**. Keep the page open until it reports completion. Batches can be retried safely.
4. Manage names, descriptions, categories, images/galleries, prices, attributes, sizes, colours, variations and stock under **Products**. New products appear automatically. Native WooCommerce product pages use the current fields and variation form.
5. Deactivate and delete the import plugin when finished. Products, photographs and measurements remain in the database/Media Library; the theme does not need the plugin.

The plugin never imports automatically. It adds missing starter products, copies photographs into the Media Library and imports the supplied ALBA/ARIA/COCO size tables when the size-table editor is available. Existing products are skipped, including edited prices, descriptions, photos, variation stock and custom SKUs. Previously imported products with blank SKUs receive stable `slovenka-*` SKUs so the standard CSV importer can match them later. Existing size-table assignments are preserved. Interrupted records created by this importer resume; existing unrelated records are untouched.

MIJA remains an unpublished draft at 220 KM with no invented photos, sizes or description. Legacy product-page links redirect to native WooCommerce products. Products hidden/deleted in WooCommerce do not leave a stale static detail page visible.

## Alternative: standard CSV import

`slovenka-products-csv-and-images.zip` contains `products.csv`, full-size photos and the measurement source. It is a data archive, not an installable theme or plugin.

1. Extract it. Upload the contents of `images/` through **Media → Add New** before importing the CSV. Keep the filenames unchanged. WooCommerce can match image filenames already in Media; WordPress creates responsive image sizes.
2. On sites using the old theme import, run the optional plugin once first to match existing products and assign missing SKUs. It preserves their current fields. This matches the original blank-SKU catalogue before CSV use. If you have assigned custom SKUs, use a fresh live-store export for updates instead: the starter CSV cannot match different SKUs and could create duplicates. The optional plugin itself matches old catalogue metadata and preserves custom SKUs.
3. Open **Products → Import**, choose `products.csv`, and review the column mapping. Parent rows precede variations and use stable SKUs. `colour` and `size` are local product attributes; parent variation references use SKUs, not installation-specific post IDs. Metadata columns can be imported as metadata if you want legacy aliases.
4. For the starter import, leave **Update existing products** unchecked so matching existing SKUs are skipped. Do not use the starter CSV with updates enabled after editing products; use a fresh WooCommerce export or a deliberately edited update file.
5. For later bulk changes, export your live catalogue from WooCommerce, edit that export, and re-import it with **Update existing products** selected. Map only fields you intend to update.

The CSV route does not automatically import the separate measurement JSON; upload manufacturer XLSX tables using Products → Tabele mjera and assign them to products, or use the optional plugin for the supplied tables.

The catalogue is sourced from the supplied static project as a starter import. Media and product records live in WordPress afterwards. Product translations require your normal WooCommerce translation workflow; changing the site's language does not select a separate static copy of product data.

CSV schema and import behavior: https://woocommerce.com/document/product-csv-importer-exporter/
