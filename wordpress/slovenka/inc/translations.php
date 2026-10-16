<?php
if (!defined('ABSPATH')) { exit; }
function slovenka_storefront_translations() {
    static $strings;
    if (!isset($strings)) { $strings = json_decode(file_get_contents(get_theme_file_path('languages/storefront-bs.json')), true) ?: []; }
    return $strings;
}
function slovenka_translate_storefront() {
    return !is_admin() && !(defined('WP_CLI') && WP_CLI) && !wp_doing_cron()
        && (!(defined('REST_REQUEST') && REST_REQUEST) || slovenka_store_api_request())
        && slovenka_language() === 'bs';
}
function slovenka_has_installed_woocommerce_translation() {
    $prefix = WP_LANG_DIR . '/plugins/woocommerce-bs_BA';
    return is_readable($prefix . '.mo') || is_readable($prefix . '.l10n.php');
}
add_filter('gettext_woocommerce', function ($translation, $text) {
    if (!slovenka_translate_storefront()) { return $translation; }
    if ($translation !== $text && slovenka_has_installed_woocommerce_translation()) { return $translation; }
    $value = slovenka_storefront_translations()[$text] ?? null;
    return is_string($value) ? $value : $translation;
}, 20, 2);
add_filter('ngettext_woocommerce', function ($translation, $single, $plural, $number) {
    if (!slovenka_translate_storefront()) { return $translation; }
    if ($translation !== $single && $translation !== $plural && slovenka_has_installed_woocommerce_translation()) { return $translation; }
    $values = slovenka_storefront_translations()[$single] ?? null;
    if (!is_array($values)) { return $translation; }
    $n = abs((int)$number);
    $index = $n % 10 === 1 && $n % 100 !== 11 ? 0 : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14)) ? 1 : 2);
    return $values[$index] ?? $translation;
}, 20, 4);
// Only replace WooCommerce's untouched English default. Merchant policy copy
// remains editable and is never rewritten or saved by the theme.
add_filter('woocommerce_get_privacy_policy_text', function ($text) {
    if (!slovenka_translate_storefront()) { return $text; }
    foreach ([
        'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.',
        'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.',
    ] as $source) {
        if (trim($text) === sprintf($source, '[privacy_policy]')) { return sprintf(__($source, 'woocommerce'), '[privacy_policy]'); }
    }
    return $text;
});
// Method titles/descriptions may be saved as English defaults in Woo settings,
// bypassing gettext on later requests. Translate exact stock copy at display time.
function slovenka_translate_default_method_copy($text) {
    if (!slovenka_translate_storefront()) { return $text; }
    $sources = [
        'Free shipping', 'Flat rate', 'Local pickup', 'Direct bank transfer',
        'Cash on delivery', 'Check payments', 'Pay with cash upon delivery.',
        'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.',
    ];
    return in_array($text, $sources, true) ? __($text, 'woocommerce') : $text;
}
foreach (['woocommerce_shipping_rate_label','woocommerce_gateway_title','woocommerce_gateway_description'] as $hook) {
    add_filter($hook, 'slovenka_translate_default_method_copy');
}
function slovenka_script_translation_file($handle) {
    $src = wp_scripts()->registered[$handle]->src ?? '';
    $path = wp_parse_url($src, PHP_URL_PATH) ?: '';
    $position = strpos($path, '/assets/');
    if ($position === false) { return ''; }
    $relative = str_replace('.min.js', '.js', substr($path, $position + 1));
    $fallback = get_theme_file_path('languages/woocommerce-js/woocommerce-bs_BA-' . md5($relative) . '.json');
    return is_readable($fallback) ? $fallback : '';
}
add_filter('load_script_translation_file', function ($file, $handle, $domain) {
    if ($domain !== 'woocommerce' || !slovenka_translate_storefront() || ($file && is_readable($file))) { return $file; }
    return slovenka_script_translation_file($handle) ?: $file;
}, 20, 3);
add_filter('load_script_translations', function ($translations, $file, $handle, $domain) {
    if ($domain !== 'woocommerce' || !slovenka_translate_storefront()) { return $translations; }
    $fallback = slovenka_script_translation_file($handle);
    if (!$fallback || $fallback === $file) { return $translations; }
    $base = json_decode(file_get_contents($fallback), true);
    $installed = json_decode($translations, true);
    if (!is_array($installed)) { return $translations; }
    // Keep nonempty merchant-installed translations and fill only missing keys.
    $messages = $installed['locale_data']['messages'] ?? $installed['locale_data']['woocommerce'] ?? [];
    $messages = array_filter($messages, static fn($values)=>is_array($values) && !in_array('', $values, true));
    $base['locale_data']['messages'] = $messages + $base['locale_data']['messages'];
    return wp_json_encode($base);
}, 20, 4);
add_action('wp_enqueue_scripts', function () {
    if (!class_exists('WooCommerce') || !(has_block('woocommerce/cart') || has_block('woocommerce/checkout'))) { return; }
    wp_enqueue_script('wp-api-fetch');
    wp_add_inline_script('wp-api-fetch', 'wp.apiFetch.use(function(options,next){if(decodeURIComponent(options.path||options.url||"").includes("/wc/store/")){options.headers=Object.assign({},options.headers,{"X-Slovenka-Language":' . wp_json_encode(slovenka_language()) . '});}return next(options);});', 'after');
}, 110);
