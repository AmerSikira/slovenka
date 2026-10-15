<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/inc/import.php';
require_once __DIR__ . '/inc/render.php';
require_once __DIR__ . '/inc/language.php';
require_once __DIR__ . '/inc/woocommerce.php';
require_once __DIR__ . '/inc/size-tables.php';
require_once __DIR__ . '/inc/forms.php';
add_action('after_setup_theme', function () {
    load_theme_textdomain('slovenka', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('automatic-feed-links');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height'=>60, 'width'=>260, 'flex-height'=>true, 'flex-width'=>true]);
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style('styles.css');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus(['primary'=>__('Primary navigation','slovenka'), 'footer'=>__('Footer navigation','slovenka')]);
});
add_action('wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('slovenka', get_theme_file_uri('styles.css'), [], $version);
    wp_enqueue_style('slovenka-native', get_theme_file_uri('native.css'), ['slovenka'], $version);
    wp_enqueue_script('slovenka-theme', get_theme_file_uri('theme.js'), [], $version, ['in_footer'=>true]);
    wp_add_inline_script('slovenka-theme', 'window.SlovenkaForms = ' . wp_json_encode(['ajaxUrl'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('mk-commerce')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';', 'before');
    if (is_singular() && comments_open() && get_option('thread_comments')) { wp_enqueue_script('comment-reply'); }
    if (class_exists('WooCommerce')) {
        wp_enqueue_style('slovenka-products', get_theme_file_uri('products.css'), ['slovenka-native'], $version);
        wp_enqueue_style('slovenka-notices', get_theme_file_uri('notices.css'), ['slovenka-native'], $version);
    }
    // Editorial enhancements are registered with WordPress and have explicit dependencies.
    if (is_page()) {
        $content = get_post_field('post_content', get_queried_object_id());
        if (str_contains($content, 'hero-copy') || str_contains($content, 'cooperation-section') || str_contains($content, 'data-count-to')) {
            wp_enqueue_script('slovenka-gsap', get_theme_file_uri('assets/vendor/gsap/gsap-3.15.0.min.js'), [], $version, ['in_footer'=>true]);
            wp_enqueue_script('slovenka-motion', get_theme_file_uri('motion.js'), ['slovenka-gsap'], $version, ['in_footer'=>true]);
        }
        if (str_contains($content, 'data-contact-map')) {
            wp_enqueue_style('slovenka-map', get_theme_file_uri('assets/vendor/maplibre/maplibre-gl.css'), [], $version);
            wp_enqueue_script('slovenka-maplibre', get_theme_file_uri('assets/vendor/maplibre/maplibre-gl.js'), [], $version, ['in_footer'=>true]);
            wp_enqueue_script('slovenka-contact-map', get_theme_file_uri('contact-map.js'), ['slovenka-maplibre'], $version, ['in_footer'=>true]);
        }
    }
}, 100);
add_action('wp_head', function () {
    if (!has_site_icon()) { echo '<link rel="icon" href="' . esc_url(get_theme_file_uri('assets/favicon.svg')) . '">'; }
});
// Editorial translations are intentional page content; all other routes use WP's locale/direction.
add_filter('language_attributes', function ($attributes) {
    $page = slovenka_page();
    if (!$page || !in_array($page['lang'], ['bs','en'], true)) { return $attributes; }
    return 'lang="' . esc_attr($page['lang']) . '" dir="ltr"';
}, 5);
