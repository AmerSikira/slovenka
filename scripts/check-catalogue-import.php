<?php
/** Run on an EMPTY, disposable localhost store with WooCommerce and the import plugin active. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
function catalogue_check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
catalogue_check(function_exists('slovenka_catalogue_data'), 'Activate the independent catalogue plugin.');
catalogue_check(!get_posts(['post_type'=>'product','post_status'=>'any','fields'=>'ids','numberposts'=>1]), 'This test needs an empty product database.');
$data = slovenka_catalogue_data();
foreach ($data['products'] as $item) { slovenka_catalogue_import_product($item, $data); }
slovenka_catalogue_import_tables($data);
$expected = ['pepito'=>123.5,'signature'=>123.5,'signature-trousers'=>65,'ami'=>123.5,'coco'=>123.5,'jakna-hasi'=>190,'over'=>175,'mija'=>220,'aria'=>75,'hasi'=>65,'icon-extra'=>75,'alba'=>65];
foreach ($expected as $key=>$price) {
    $id = wc_get_product_id_by_sku('slovenka-' . $key);
    $product = wc_get_product($id);
    catalogue_check($product && (float)$product->get_price() === (float)$price, 'Incorrect price: ' . $key);
    catalogue_check($product->get_status() === ($key === 'mija' ? 'draft' : 'publish'), 'Incorrect status: ' . $key);
    foreach ($product->get_children() as $child_id) {
        $child = wc_get_product($child_id);
        catalogue_check($child->get_sku() && (float)$child->get_regular_price() === (float)$price, 'Variation missing price/SKU: ' . $key);
        catalogue_check($child->get_image_id() && is_file(get_attached_file($child->get_image_id())), 'Variation photo missing');
    }
}
catalogue_check((int)wp_count_posts('product_variation')->publish === 90, 'Wrong variation count');
catalogue_check(count(get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids'])) === 56, 'Wrong imported photo count');
$coco = wc_get_product(wc_get_product_id_by_sku('slovenka-coco'));
$coco->set_name('Edited COCO <test> & name');
$coco->set_description('<p>Edited description from WooCommerce.</p>');
$coco->set_short_description('Edited live label');
$coco->set_gallery_image_ids(array_reverse($coco->get_gallery_image_ids()));
$coco->save();
$child = wc_get_product($coco->get_children()[0]);
$child->set_regular_price('141'); $child->set_manage_stock(true); $child->set_stock_quantity(7); $child->save();
$before = [wc_get_product($coco->get_id())->get_data(), wc_get_product($child->get_id())->get_data(), wp_count_posts('product'), wp_count_posts('product_variation'), wp_count_posts('attachment')];
foreach ($data['products'] as $item) { slovenka_catalogue_import_product($item, $data); }
slovenka_catalogue_import_tables($data);
$after = [wc_get_product($coco->get_id())->get_data(), wc_get_product($child->get_id())->get_data(), wp_count_posts('product'), wp_count_posts('product_variation'), wp_count_posts('attachment')];
catalogue_check($before == $after, 'Repeat import overwrote edits or duplicated records');
// Leave the baseline prices/copy intact for the storefront checks, but verify their edits survived above.
$coco->set_name('COCO — Sako s dvorednim kopčanjem'); $coco->save();
$child->set_regular_price('123.5'); $child->save(); WC_Product_Variable::sync($coco->get_id());
echo "PASS: independent import, 12 products/90 variations/56 photos, stable SKUs, MIJA draft, exact prices, native images, repeat import preserves edited copy/gallery/price/stock and counts.\n";
