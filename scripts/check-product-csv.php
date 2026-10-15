<?php
/** Standard WooCommerce CSV importer check on an empty disposable localhost store. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
if (!$args || !is_file($args[0]) || get_posts(['post_type'=>'product','post_status'=>'any','fields'=>'ids','numberposts'=>1])) { throw new RuntimeException('Pass the generated CSV on an empty product store.'); }
// Simulate the documented Media upload prerequisite with local packaged originals.
foreach (slovenka_catalogue_data()['images'] as $image) { slovenka_catalogue_media($image['src']); }
require_once WC_ABSPATH . 'includes/import/abstract-wc-product-importer.php';
require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
require_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
class Slovenka_QA_CSV_Controller extends WC_Product_CSV_Importer_Controller {
    public function mapping($headers) { return $this->auto_map_columns($headers, false); }
}
$reader = new WC_Product_CSV_Importer($args[0], ['parse'=>false]);
$mapping = (new Slovenka_QA_CSV_Controller())->mapping($reader->get_raw_keys());
$import = new WC_Product_CSV_Importer($args[0], ['parse'=>true, 'mapping'=>$mapping, 'prevent_timeouts'=>false, 'update_existing'=>false]);
$result = $import->import();
// Complete WooCommerce's normal end-of-request parent synchronization.
WC_Post_Data::do_deferred_product_sync();
if ($result['failed'] || count($result['imported']) !== 12 || count($result['imported_variations']) !== 90) {
    throw new RuntimeException('CSV import mismatch: ' . wp_json_encode(['imported'=>count($result['imported']),'variations'=>count($result['imported_variations']),'first_error'=>$result['failed'][0] ?? null]));
}
foreach (['pepito'=>123.5,'signature'=>123.5,'signature-trousers'=>65,'ami'=>123.5,'coco'=>123.5,'jakna-hasi'=>190,'over'=>175,'mija'=>220,'aria'=>75,'hasi'=>65,'icon-extra'=>75,'alba'=>65] as $key=>$price) {
    $product = wc_get_product(wc_get_product_id_by_sku('slovenka-' . $key));
    if (!$product || (float)$product->get_price() !== (float)$price || ($key === 'mija' && $product->get_status() !== 'draft')) { throw new RuntimeException('CSV product incorrect: ' . $key); }
    if ($key !== 'mija' && (!$product->get_image_id() || !$product->get_children())) { throw new RuntimeException('CSV product missing images or children'); }
}
$repeat = (new WC_Product_CSV_Importer($args[0], ['parse'=>true, 'mapping'=>$mapping, 'prevent_timeouts'=>false, 'update_existing'=>false]))->import();
if ($repeat['imported'] || $repeat['imported_variations'] || (int)wp_count_posts('product_variation')->publish !== 90) { throw new RuntimeException('Repeat CSV import duplicated products'); }
echo "PASS: standard WooCommerce auto-mapping, 12 products/90 variations, local Media filename resolution, SKU parent matching, MIJA draft, prices, no duplicate repeat import.\n";
