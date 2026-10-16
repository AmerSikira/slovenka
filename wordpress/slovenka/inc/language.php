<?php
if (!defined('ABSPATH')) { exit; }

/** Editorial page language wins; shared store routes inherit the front page. */
function slovenka_default_language() {
    $front = (int)get_option('page_on_front');
    $source = $front ? get_post_meta($front, '_slovenka_source', true) : '';
    return slovenka_data()['pages'][$source]['lang'] ?? (str_starts_with((string)get_option('WPLANG'), 'bs') ? 'bs' : 'en');
}
function slovenka_requested_language() {
    $lang = isset($_GET['lang']) && is_string($_GET['lang']) ? wp_unslash($_GET['lang']) : '';
    return in_array($lang, ['bs', 'en'], true) ? $lang : slovenka_default_language();
}
function slovenka_frontend_locale($locale) {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) { return $locale; }
    $lang = did_action('wp') ? slovenka_language() : slovenka_requested_language();
    return $lang === 'bs' ? 'bs_BA' : 'en_US';
}
add_filter('locale', 'slovenka_frontend_locale', 20);
add_filter('determine_locale', 'slovenka_frontend_locale', 20);

// Load the bundled community translation only on the storefront. Merchant-installed
// WooCommerce language packs take precedence and can be updated normally.
add_action('wp', function () {
    if (is_admin() || !class_exists('WooCommerce')) { return; }
    unload_textdomain('woocommerce', true);
    if (slovenka_language() !== 'bs') { return; }
    $installed = WP_LANG_DIR . '/plugins/woocommerce-bs_BA.mo';
    $file = is_readable($installed) ? $installed : get_theme_file_path('languages/woocommerce-bs_BA.mo');
    load_textdomain('woocommerce', $file, 'bs_BA');
}, 1);

/** Query parameters make language explicit and keep shared caches deterministic. */
function slovenka_language_url($url, $lang = null) {
    if (!is_string($url) || !$url || is_admin()) { return $url; }
    $host = wp_parse_url($url, PHP_URL_HOST);
    if ($host && $host !== wp_parse_url(home_url('/'), PHP_URL_HOST)) { return $url; }
    if (str_starts_with($url, '#') || preg_match('~^(mailto:|tel:|javascript:)~i', $url)) { return $url; }
    return add_query_arg('lang', $lang ?? slovenka_language(), $url);
}
foreach (['woocommerce_get_shop_page_permalink', 'woocommerce_get_cart_url', 'woocommerce_get_checkout_url', 'woocommerce_get_myaccount_page_permalink', 'woocommerce_get_checkout_order_received_url', 'get_pagenum_link'] as $hook) {
    add_filter($hook, 'slovenka_language_url');
}
add_filter('woocommerce_ajax_get_endpoint', function ($url) {
    // WooCommerce replaces this literal token in checkout.js. add_query_arg()
    // encodes its percent signs, preventing order review and checkout requests.
    return str_replace('%25%25endpoint%25%25', '%%endpoint%%', slovenka_language_url($url));
});
// WooCommerce redirects plain-permalink shop page links to this archive URL.
add_filter('post_type_archive_link', function ($url, $type) {
    return $type === 'product' ? slovenka_language_url($url) : $url;
}, 10, 2);
add_filter('post_type_link', function ($url, $post) {
    return $post->post_type === 'product' ? slovenka_language_url($url) : $url;
}, 10, 2);
add_filter('term_link', function ($url, $term) {
    return str_starts_with($term->taxonomy, 'product_') ? slovenka_language_url($url) : $url;
}, 10, 2);
add_filter('page_link', function ($url, $id) {
    if (!function_exists('wc_get_page_id')) { return $url; }
    foreach (['shop', 'cart', 'checkout', 'myaccount'] as $role) {
        if ((int)$id === wc_get_page_id($role)) { return slovenka_language_url($url); }
    }
    return $url;
}, 10, 2);
// Imported editorial content contains saved links, so localize those as well.
add_filter('the_content', function ($content) {
    if (is_admin()) { return $content; }
    $html = new WP_HTML_Tag_Processor($content);
    while ($html->next_tag('a')) {
        $url = $html->get_attribute('href');
        if (is_string($url)) { $html->set_attribute('href', slovenka_language_url($url)); }
    }
    return $html->get_updated_html();
}, 20);
add_filter('nav_menu_link_attributes', function ($attributes) {
    if (isset($attributes['href'])) { $attributes['href'] = slovenka_language_url($attributes['href']); }
    return $attributes;
});
// Translate technical attribute keys used by the starter import, keeping merchant
// labels and product descriptions under WooCommerce's normal content workflow.
add_filter('woocommerce_attribute_label', function ($label) {
    if (is_admin() || slovenka_language() !== 'bs') { return $label; }
    return ['colour'=>'Boja', 'color'=>'Boja', 'size'=>'Veličina'][$label] ?? $label;
});
add_filter('woocommerce_variation_option_name', function ($name, $term, $attribute) {
    if (is_admin() || slovenka_language() !== 'bs' || !in_array($attribute, ['colour', 'color', 'pa_colour', 'pa_color'], true)) { return $name; }
    return ['black'=>'Crna', 'brown'=>'Smeđa', 'white'=>'Bijela', 'grey'=>'Siva', 'gray'=>'Siva', 'charcoal'=>'Antracit', 'burgundy'=>'Bordo', 'bronze'=>'Bronzana', 'beige'=>'Bež', 'navy'=>'Tamnoplava', 'blue'=>'Plava', 'green'=>'Zelena'][strtolower($name)] ?? $name;
}, 10, 3);
add_filter('woocommerce_breadcrumb_home_url', fn() => slovenka_home_url());
add_filter('woocommerce_get_endpoint_url', 'slovenka_language_url');
add_filter('get_custom_logo', function ($logo) {
    $html = new WP_HTML_Tag_Processor($logo);
    if ($html->next_tag('a')) { $html->set_attribute('href', slovenka_home_url()); }
    return $html->get_updated_html();
});
