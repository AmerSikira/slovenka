<?php
/** Price-only CSV verification on a disposable localhost store with the starter catalogue. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
if (!$args || !is_file($args[0])) { throw new RuntimeException('Pass slovenka-price-updates.csv.'); }
require_once WC_ABSPATH . 'includes/import/abstract-wc-product-importer.php';
require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
$rows = array_map('str_getcsv', file($args[0], FILE_IGNORE_NEW_LINES));
array_shift($rows);
$before = [];
$protected = ['name','description','short_description','status','sku','sale_price','image_id','gallery_image_ids','stock_status','stock_quantity','manage_stock','attributes'];
foreach ($rows as [$sku, $price]) {
    $id = wc_get_product_id_by_sku($sku);
    $product = wc_get_product($id);
    if (!$product) { throw new RuntimeException('Missing starter SKU: ' . $sku); }
    $before[$id] = ['regular_price'=>$product->get_regular_price(), 'fields'=>array_intersect_key($product->get_data(), array_flip($protected))];
}
try {
    // Give the importer a real price change to apply, while keeping other fields protected.
    $probe = wc_get_product(array_key_first($before));
    $probe->set_regular_price('999.99'); $probe->save();
    $importer = new WC_Product_CSV_Importer($args[0], ['parse'=>true,'mapping'=>['SKU'=>'sku','Regular price'=>'regular_price'],'prevent_timeouts'=>false,'update_existing'=>true]);
    $result = $importer->import();
    WC_Post_Data::do_deferred_product_sync();
    if ($result['failed'] || $result['imported'] || $result['imported_variations'] || count($result['updated']) !== count($rows)) { throw new RuntimeException('Price-only import did not update exactly the matching records: ' . wp_json_encode($result)); }
    foreach ($rows as [$sku, $price]) {
        $id = wc_get_product_id_by_sku($sku);
        $product = wc_get_product($id);
        if ((float)$product->get_regular_price() !== (float)$price) { throw new RuntimeException('Wrong updated price: ' . $sku); }
        if (array_intersect_key($product->get_data(), array_flip($protected)) !== $before[$id]['fields']) { throw new RuntimeException('Price update changed other product fields: ' . $sku); }
    }
    echo 'PASS: ' . count($rows) . " SKU-matched regular price updates; names, descriptions, sales, images, stock, attributes and publication preserved; no new products\n";
} finally {
    $parents = [];
    foreach ($before as $id=>$snapshot) {
        $product = wc_get_product($id);
        $product->set_regular_price($snapshot['regular_price']); $product->save();
        if ($product->get_parent_id()) { $parents[$product->get_parent_id()] = true; }
    }
    foreach (array_keys($parents) as $id) { WC_Product_Variable::sync($id); }
}
