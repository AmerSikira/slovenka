<?php
if (!defined('ABSPATH')) { exit; }
function slovenka_data() {
    static $data;
    if ($data === null) { $data = json_decode(file_get_contents(get_template_directory() . '/data/site.json'), true, 512, JSON_THROW_ON_ERROR); }
    return $data;
}
function slovenka_find($type, $key, $value) {
    $ids = get_posts(['post_type' => $type, 'post_status' => 'any', 'meta_key' => $key, 'meta_value' => $value, 'fields' => 'ids', 'posts_per_page' => 1]);
    return $ids ? (int) $ids[0] : 0;
}
function slovenka_checkpoint() {
    if (microtime(true) > ($GLOBALS['slovenka_import_deadline'] ?? PHP_FLOAT_MAX)) { throw new RuntimeException('__continue__'); }
}
function slovenka_import_site() {
    $version = '2';
    if (get_option('slovenka_pages_version') === $version && (!class_exists('WooCommerce') || get_option('slovenka_store_pages_version') === $version)) { return; }
    // Atomic lock with expiry permits safe recovery after a killed request.
    if ((int) get_option('slovenka_import_lock') < time() - 300) { delete_option('slovenka_import_lock'); }
    if (!add_option('slovenka_import_lock', time(), '', false)) { return; }
    $errors = [];
    $GLOBALS['slovenka_import_deadline'] = microtime(true) + 12;
    // This trusted bundle contains forms; preserve them even during cron/CLI imports.
    kses_remove_filters();
    try {
        $data = slovenka_data();
        $pages = get_option('slovenka_pages', []);
        if (get_option('slovenka_pages_version') !== $version) {
            foreach ($data['pages'] as $file => $page) {
                $id = slovenka_find('page', '_slovenka_source', $file);
                if (!$id) {
                    $content = $page['content'];
                    if (in_array($page['route'], ['/cart', '/checkout'], true)) {
                        $content = $page['route'] === '/cart' ? '[woocommerce_cart]' : '[woocommerce_checkout]';
                    }
                    if ($page['route'] === '/order') { $content = '<p>Detalji narudžbe dostupni su na potvrdi kupovine. / Order details are available in your checkout confirmation.</p>'; }
                    $id = wp_insert_post(wp_slash(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page['title'], 'post_name' => pathinfo($file, PATHINFO_FILENAME), 'post_content' => $content, 'meta_input' => ['_slovenka_source' => $file]]), true);
                    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
                }
                $pages[$file] = (int) $id;
                update_option('slovenka_pages', $pages, false);
            }
            update_option('show_on_front', 'page');
            update_option('page_on_front', $pages['index.html']);
            update_option('wp_page_for_privacy_policy', $pages['support-privacy.html']);
            update_option('slovenka_pages_version', $version, false);
        }
        if (class_exists('WooCommerce') && get_option('slovenka_store_pages_version') !== $version) {
            foreach (['cart' => 'cart.html', 'checkout' => 'checkout.html', 'shop' => 'shop.html', 'terms' => 'support-terms.html'] as $role => $file) {
                // Keep a record of the previous assignments for deployment rollback.
                $previous = get_option('slovenka_previous_store_pages', []);
                if (!array_key_exists($role, $previous)) {
                    $previous[$role] = get_option('woocommerce_' . $role . '_page_id');
                    update_option('slovenka_previous_store_pages', $previous, false);
                }
                update_option('woocommerce_' . $role . '_page_id', $pages[$file]);
            }
            if (!get_option('slovenka_currency_initialized')) {
                update_option('woocommerce_currency', 'BAM');
                update_option('slovenka_currency_initialized', 1, false);
            }
            update_option('slovenka_store_pages_version', $version, false);
        }
    } catch (Throwable $error) {
        if ($error->getMessage() === '__continue__') {
            $errors[] = 'MK Slovenka content import is running in the background. It continues automatically in small batches.';
            if (!wp_next_scheduled('slovenka_continue_import')) { wp_schedule_single_event(time() + 5, 'slovenka_continue_import'); }
        } else {
            $errors[] = 'MK Slovenka import paused: ' . $error->getMessage() . ' The import will retry on the next admin request.';
        }
    } finally {
        update_option('slovenka_import_errors', $errors, false);
        delete_option('slovenka_import_lock');
        kses_init();
    }
}
