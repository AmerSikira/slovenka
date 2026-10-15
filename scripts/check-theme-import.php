<?php
/** Run with wp eval-file against a disposable localhost WordPress installation. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Run this integration check only on a disposable localhost installation.');
}
$admins = get_users(['role'=>'administrator', 'number'=>1]);
if (!$admins) { throw new RuntimeException('Test installation needs an administrator account.'); }
wp_set_current_user($admins[0]->ID);
kses_init();
function check_import($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$expected = ['pepito'=>123.5, 'signature'=>123.5, 'signature-trousers'=>65, 'ami'=>123.5, 'coco'=>123.5, 'jakna-hasi'=>190, 'over'=>175, 'mija'=>220, 'aria'=>75, 'hasi'=>65, 'icon-extra'=>75, 'alba'=>65];
check_import(count(get_option('slovenka_pages')) === 50, 'Not all bilingual pages were imported');
check_import(count(get_option('slovenka_media')) === 72, 'Not all displayed images were imported');
foreach ($expected as $key => $price) {
    $id = slovenka_find('product', '_slovenka_product', $key);
    $product = wc_get_product($id);
    check_import($product && (float) $product->get_price() === (float) $price, 'Incorrect price: ' . $key);
    check_import($product->get_status() === ($key === 'mija' ? 'draft' : 'publish'), 'Incorrect publication state: ' . $key);
    foreach ($product->get_children() as $variation_id) { check_import((float) wc_get_product($variation_id)->get_regular_price() === (float) $price, 'Incorrect variation price: ' . $key); }
}
check_import(wc_get_product(slovenka_find('product', '_slovenka_product', 'hasi'))->get_attribute('size') === '36 | 38 | 40 | 42 | 44 | 46', 'ICON sizes incorrect');
check_import(wc_get_product(slovenka_find('product', '_slovenka_product', 'icon-extra'))->get_attribute('size') === '48 | 50 | 52 | 54', 'ICON EXTRA sizes incorrect');
$pages = get_option('slovenka_pages');
check_import(str_contains(get_post($pages['contact.html'])->post_content, '<form'), 'Contact form was stripped');
check_import(str_contains(get_post($pages['cart.html'])->post_content, '[woocommerce_cart]'), 'Production cart is not native WooCommerce');
$counts = [];
foreach (['page', 'attachment', 'product', 'product_variation'] as $type) { $counts[$type] = wp_count_posts($type); }
$contact = get_post($pages['contact.html'])->post_content;
$variation = wc_get_product(wc_get_product(slovenka_find('product', '_slovenka_product', 'hasi'))->get_children()[0]);
$old_price = $variation->get_regular_price();
try {
    wp_update_post(wp_slash(['ID'=>$pages['contact.html'], 'post_content'=>$contact . '<p>Editorial change to preserve.</p>']));
    $variation->set_regular_price('66'); $variation->save();
    delete_option('slovenka_pages_version'); delete_option('slovenka_products_version');
    slovenka_import_site();
    check_import(get_option('slovenka_import_errors') === [], 'Retry did not complete');
    check_import(str_contains(get_post($pages['contact.html'])->post_content, 'Editorial change to preserve.'), 'Reactivation overwrote edited content');
    check_import(wc_get_product($variation->get_id())->get_regular_price() === '66', 'Reactivation overwrote edited prices');
    foreach ($counts as $type => $count) { check_import($count == wp_count_posts($type), 'Reactivation duplicated ' . $type); }
} finally {
    wp_update_post(wp_slash(['ID'=>$pages['contact.html'], 'post_content'=>$contact]));
    $variation->set_regular_price($old_price); $variation->save();
    WC_Product_Variable::sync($variation->get_parent_id());
}
// A partial product import must repair its missing variation without duplicating siblings.
$product_id = slovenka_find('product', '_slovenka_product', 'pepito');
$children = wc_get_product($product_id)->get_children();
wp_delete_post($children[0], true);
delete_post_meta($product_id, '_slovenka_import_complete');
delete_option('slovenka_products_version');
slovenka_import_site();
check_import(count(wc_get_product($product_id)->get_children()) === count($children), 'Interrupted product import did not repair variations');
check_import(get_option('slovenka_import_errors') === [], 'Import errors remain');
echo "PASS: prices, ICON sizes, MIJA draft, all pages/images, native cart, idempotency, edited content/prices, interrupted variation recovery.\n";
