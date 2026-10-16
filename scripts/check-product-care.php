<?php
// Run with wp eval-file on a disposable WooCommerce store with Slovenka active.
$previous_product = $GLOBALS['product'] ?? null;
$previous_post_object = $GLOBALS['post'] ?? null;
$previous_post = $_POST;
$care_product = new WC_Product_Simple();
$care_product->set_name('Composition and care QA');
$care_product->set_description('Existing product description');
$care_product->save();
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
};
try {
    $_POST['_slovenka_composition_care'] = wp_slash("95% pamuk\n5% elastan\n<p>Prati na 30°C.</p><script>alert(1)</script>");
    do_action('woocommerce_admin_process_product_object', $care_product);
    $care_product->save();
    $care_product = wc_get_product($care_product->get_id());
    $care = $care_product->get_meta('_slovenka_composition_care');
    $check(str_contains($care, '95% pamuk') && !str_contains($care, '<script>'), 'Care must persist as sanitized HTML');
    unset($_POST['_slovenka_composition_care']);
    do_action('woocommerce_admin_process_product_object', $care_product);
    $check($care_product->get_meta('_slovenka_composition_care') === $care, 'Missing field must preserve saved care');
    $GLOBALS['product'] = $care_product;
    $GLOBALS['post'] = get_post($care_product->get_id());
    ob_start(); slovenka_product_accordions(); $html = ob_get_clean();
    $check(str_contains($html, '95% pamuk<br') && str_contains($html, 'Prati na 30°C.'), 'Storefront must render care and line breaks');
    $check(!str_contains($html, 'product-detail-description'), 'Fit/details accordion must be removed');
    $_POST['_slovenka_composition_care'] = '';
    do_action('woocommerce_admin_process_product_object', $care_product);
    $care_product->save();
    $check(wc_get_product($care_product->get_id())->get_meta('_slovenka_composition_care') === '', 'Care must be clearable');
    $check($care_product->get_description() === 'Existing product description', 'Original description must be preserved');
    ob_start(); slovenka_product_accordions(); $fallback = ob_get_clean();
    $check(str_contains($fallback, 'Contact us for composition') || str_contains($fallback, 'Za informacije o sastavu'), 'Empty care must show the contact fallback');
    echo "PASS: care saves, sanitizes, preserves absent input, renders line breaks, clears, and keeps descriptions; fit/details removed.\n";
} finally {
    wp_delete_post($care_product->get_id(), true);
    $GLOBALS['product'] = $previous_product;
    $GLOBALS['post'] = $previous_post_object;
    $_POST = $previous_post;
}
