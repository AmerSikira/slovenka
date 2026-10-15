<?php
/** Run against a disposable localhost store with WooCommerce active. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
function check_import($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$admins = get_users(['role'=>'administrator','number'=>1]); wp_set_current_user($admins[0]->ID);
$ids = [];
try {
    $product = new WC_Product_Simple(); $product->set_name('Edited native QA'); $product->set_status('publish'); $product->set_regular_price('57'); $product->set_description('Merchant description'); $ids[] = $product->save();
    $before = []; foreach (['product','product_variation','attachment'] as $type) { $before[$type] = wp_count_posts($type); }
    $snapshot = wc_get_product($product->get_id())->get_data();
    $options = []; foreach (['woocommerce_currency','woocommerce_shop_page_id','woocommerce_cart_page_id','woocommerce_checkout_page_id','page_on_front','show_on_front','wp_page_for_privacy_policy'] as $key) { $options[$key] = get_option($key); }
    slovenka_import_site();
    check_import($snapshot == wc_get_product($product->get_id())->get_data(), 'Setup changed merchant product');
    foreach ($before as $type=>$counts) { check_import(wp_count_posts($type) == $counts, 'Setup mutated ' . $type); }
    foreach ($options as $key=>$value) { check_import(get_option($key) === $value, 'Setup changed setting ' . $key); }
    $edited = '<p class="muted">Intro</p><p>MERCHANT_NOTICE</p><!-- wp:shortcode -->[merchant_shortcode]<!-- /wp:shortcode -->';
    $prepared = slovenka_prepare_content($edited, ['route'=>'/shop','lang'=>'bs'], []);
    check_import(str_contains($prepared, 'MERCHANT_NOTICE') && str_contains($prepared, '[merchant_shortcode]'), 'Shop edit lost');
    $preview = '<section class="listing"><h1>Old intro</h1><p class="muted">Intro</p><div class="listing-toolbar"><div>Old filters</div></div><div class="product-grid listing-grid"><article>Old product</article></div></section><p>MERCHANT_NOTICE</p>';
    $prepared = slovenka_prepare_content($preview, ['route'=>'/shop','lang'=>'bs'], []);
    check_import(str_contains($prepared, 'MERCHANT_NOTICE') && str_contains($prepared, 'Intro') && !str_contains($prepared, 'Old product') && !str_contains($prepared, '<h1>'), 'Shop legacy migration incorrect');
    check_import(!function_exists('slovenka_catalogue') && !function_exists('slovenka_import_product'), 'Custom catalogue remains');
    foreach (slovenka_data()['pages'] as $page) { check_import(!str_starts_with($page['route'], '/product/'), 'Product seed remains'); }
    echo "PASS: optional setup preserves merchant settings/products/media, edited shop content and blocks; removes only obsolete catalogue markup; no product templates or registry.\n";
} finally { foreach ($ids as $id) { wp_delete_post($id, true); } }
