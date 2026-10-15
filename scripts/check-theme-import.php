<?php
/** Theme isolation and live edits; use wp eval-file against a disposable localhost store. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
function check_import($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$admins = get_users(['role'=>'administrator','number'=>1]); wp_set_current_user($admins[0]->ID);
$ids = []; $attribute_ids = []; $term_ids = [];
try {
    $product = new WC_Product_Simple();
    $product->set_name('Live catalogue test'); $product->set_status('publish'); $product->set_regular_price('42'); $product->set_description('Live description');
    $id = $product->save(); $ids[] = $id;
    $product->set_name('Edited catalogue name'); $product->set_regular_price('57'); $product->set_short_description('Current short copy'); $product->save();
    $media = get_posts(['post_type'=>'attachment','post_status'=>'inherit','fields'=>'ids','numberposts'=>3]);
    if ($media) { $product->set_image_id($media[0]); $product->set_gallery_image_ids(array_slice($media, 1)); $product->save(); }
    // A wholly new product with global colour/size attributes must also work.
    $attributes = []; $global_terms = [];
    foreach (['color'=>['Live violet'], 'size'=>['Live small','Live large']] as $name=>$values) {
        $attribute_id = wc_attribute_taxonomy_id_by_name($name);
        if (!$attribute_id) { $attribute_id = wc_create_attribute(['name'=>ucfirst($name),'slug'=>$name,'type'=>'select']); $attribute_ids[] = $attribute_id; }
        $taxonomy = 'pa_' . $name;
        if (!taxonomy_exists($taxonomy)) { register_taxonomy($taxonomy, 'product', ['public'=>false]); }
        $options = [];
        foreach ($values as $value) {
            $term = wp_insert_term($value, $taxonomy, ['slug'=>'qa-' . sanitize_title($value)]);
            check_import(!is_wp_error($term), 'Could not create global attribute fixture');
            $options[] = (int)$term['term_id']; $term_ids[] = [$term['term_id'],$taxonomy];
            $global_terms[$name][] = get_term($term['term_id'], $taxonomy);
        }
        $attribute = new WC_Product_Attribute(); $attribute->set_id($attribute_id); $attribute->set_name($taxonomy); $attribute->set_options($options); $attribute->set_visible(true); $attribute->set_variation(true); $attributes[] = $attribute;
    }
    $variable = new WC_Product_Variable(); $variable->set_name('Brand new dynamic model'); $variable->set_status('publish'); $variable->set_attributes($attributes);
    if ($media) { $variable->set_image_id($media[0]); }
    $variable_id = $variable->save(); $ids[] = $variable_id;
    $variation = new WC_Product_Variation(); $variation->set_parent_id($variable_id); $variation->set_attributes(['pa_color'=>$global_terms['color'][0]->slug,'pa_size'=>$global_terms['size'][0]->slug]); $variation->set_regular_price('83'); $variation->set_manage_stock(true); $variation->set_stock_quantity(7); $variation->set_status('publish');
    if (count($media) > 1) { $variation->set_image_id($media[1]); }
    $ids[] = $variation->save(); WC_Product_Variable::sync($variable_id);
    $before = [];
    foreach (['product','product_variation','attachment'] as $type) { $before[$type] = wp_count_posts($type); }
    $snapshot = wc_get_product($id)->get_data();
    // Force the theme's installation path even on an already-upgraded database.
    delete_option('slovenka_pages_version'); delete_option('slovenka_store_pages_version');
    slovenka_import_site();
    check_import(get_option('slovenka_import_errors') === [], 'Editorial import errors');
    check_import($snapshot == wc_get_product($id)->get_data(), 'Theme import changed a product');
    foreach ($before as $type=>$counts) {
        $now = wp_count_posts($type);
        check_import($now == $counts, 'Theme created/removed catalogue records: ' . $type);
    }
    $catalogue = slovenka_catalogue();
    $item = array_values(array_filter($catalogue['products'], fn($item) => $item['id'] === (string)$id));
    check_import($item && $item[0]['name'] === 'Edited catalogue name' && $item[0]['price'] === 57.0 && $item[0]['label']['bs'] === 'Current short copy', 'New/edited product data is stale');
    $live = array_values(array_filter($catalogue['products'], fn($item) => $item['id'] === (string)$variable_id))[0] ?? null;
    check_import($live && $live['colors'][0]['name']['bs'] === 'Live violet' && count($live['sizes']) === 2 && !array_diff($live['sizes'], ['Live small','Live large']) && $live['unavailable'] === ['Live large'], 'Global attributes or stock are stale: ' . wp_json_encode($live));
    check_import($live['price'] === 83.0 && $live['colourAttribute'] === 'pa_color', 'Variable price/global attribute mapping incorrect');
    if (count($media) > 1) { check_import($live['colors'][0]['cover'] === 'media-' . $media[1], 'Edited variation photo not used'); }
    check_import($item[0]['permalink'] === $product->get_permalink(), 'Product URL is static');
    check_import(!isset(slovenka_data()['products']), 'Theme ships a product registry');
    check_import(!function_exists('slovenka_import_product'), 'Theme can still import products');
    foreach (slovenka_data()['pages'] as $file=>$page) {
        check_import(!str_starts_with($page['route'], '/product/'), 'Theme contains static product detail pages');
        check_import(isset(get_option('slovenka_pages')[$file]), 'Editorial page missing');
    }
    echo "PASS: theme imports editorial pages only; no product/media mutations; arbitrary new products, edited names/prices/copy and native URLs appear from WooCommerce.\n";
} finally {
    foreach (array_reverse($ids) as $id) { wp_delete_post($id, true); }
    foreach ($term_ids as [$id,$taxonomy]) { wp_delete_term($id,$taxonomy); }
    foreach ($attribute_ids as $id) { wc_delete_attribute($id); }
}
