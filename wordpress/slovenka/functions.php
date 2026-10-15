<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/inc/import.php';
require_once __DIR__ . '/inc/render.php';
add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    register_nav_menus(['primary' => 'Primary navigation', 'footer' => 'Footer navigation']);
});
add_action('after_switch_theme', 'slovenka_import_site');
add_action('slovenka_continue_import', 'slovenka_import_site');
// Also resume when WooCommerce is installed after the theme, or an import was interrupted.
add_action('admin_init', function () {
    if (current_user_can('manage_options')) { slovenka_import_site(); }
});
add_action('admin_notices', function () {
    $errors = get_option('slovenka_import_errors', []);
    if (!class_exists('WooCommerce')) {
        $errors[] = 'Activate WooCommerce to finish importing products. Pages and images are already imported; the catalogue import resumes automatically.';
    }
    foreach (array_unique($errors) as $error) {
        echo '<div class="notice notice-warning"><p>' . esc_html($error) . '</p></div>';
    }
});
add_filter('woocommerce_add_to_cart_redirect', function ($url) { return wc_get_cart_url(); });
add_filter('the_content', function ($content) {
    return get_post_meta(get_the_ID(), '_slovenka_source', true) ? slovenka_html($content) : $content;
}, 20);
// Imported content is already complete semantic HTML, including forms and templates.
add_action('wp', function () {
    if (get_post_meta(get_queried_object_id(), '_slovenka_source', true)) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'wptexturize');
    }
});
