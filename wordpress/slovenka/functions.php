<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/inc/import.php';
require_once __DIR__ . '/inc/render.php';
require_once __DIR__ . '/inc/size-tables.php';
require_once __DIR__ . '/inc/forms.php';
add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    register_nav_menus(['primary' => 'Primary navigation', 'footer' => 'Footer navigation']);
});
// Load after WooCommerce's styles, including notices outside shop templates.
add_action('wp_enqueue_scripts', function () {
    if (!class_exists('WooCommerce')) { return; }
    wp_enqueue_style('slovenka-notices', get_template_directory_uri() . '/notices.css', [], (string) filemtime(get_template_directory() . '/notices.css'));
}, 100);
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

// This theme uses full-width layouts on every route, including native WC pages.
add_action('wp', function () {
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
});
