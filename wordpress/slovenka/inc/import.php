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
function slovenka_media($path) {
    $aliases = slovenka_data()['image_aliases'] ?? [];
    $path = $aliases[$path] ?? $path;
    $id = slovenka_find('attachment', '_slovenka_asset', $path);
    if ($id) { return $id; }
    // Reuse the attachment imported by an older theme instead of uploading a second copy.
    foreach ($aliases as $legacy => $current) {
        if ($current !== $path) { continue; }
        $id = slovenka_find('attachment', '_slovenka_asset', $legacy);
        if ($id) {
            update_post_meta($id, '_slovenka_asset', $path);
            return $id;
        }
    }
    slovenka_checkpoint();
    $file = get_template_directory() . '/' . $path;
    if (!is_file($file)) { throw new RuntimeException('Missing bundled image: ' . $path); }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam(basename($path));
    if (!$tmp || !copy($file, $tmp)) { throw new RuntimeException('Cannot copy image: ' . $path); }
    $id = media_handle_sideload(['name' => basename($file), 'tmp_name' => $tmp], 0);
    if (is_wp_error($id)) { @unlink($tmp); throw new RuntimeException($id->get_error_message()); }
    update_post_meta($id, '_slovenka_asset', $path);
    return $id;
}
function slovenka_import_site() {
    $version = '1';
    if (get_option('slovenka_pages_version') === $version && (!class_exists('WC_Product_Variable') || get_option('slovenka_products_version') === $version)) { return; }
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
            // Import every displayed photograph to the Media Library, not only product covers.
            $media = [];
            $paths = [];
            foreach ($data['images'] as $image) { $paths[] = $image['fallback']; }
            foreach ($data['pages'] as $page) {
                preg_match_all('~(?:src|href)=["\'](assets/images/[^"\'?]+)~', $page['content'] . $page['before'] . $page['after'], $matches);
                $paths = array_merge($paths, $matches[1]);
            }
            foreach (array_unique($paths) as $path) {
                $media[$path] = slovenka_media($path);
                update_option('slovenka_media', $media, false);
            }
            update_option('show_on_front', 'page');
            update_option('page_on_front', $pages['index.html']);
            update_option('wp_page_for_privacy_policy', $pages['support-privacy.html']);
            update_option('slovenka_pages_version', $version, false);
        }
        if (class_exists('WC_Product_Variable') && get_option('slovenka_products_version') !== $version) {
            foreach ($data['products'] as $item) { slovenka_checkpoint(); slovenka_import_product($item, $data); }
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
            update_option('slovenka_products_version', $version, false);
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
function slovenka_import_product($item, $data) {
    $id = slovenka_find('product', '_slovenka_product', $item['id']);
    if ($id && get_post_meta($id, '_slovenka_import_complete', true)) { return; }
    $draft = ($item['status'] ?? '') === 'draft';
    $product = $id ? wc_get_product($id) : ($draft ? new WC_Product_Simple() : new WC_Product_Variable());
    if (!$product) { throw new RuntimeException('Cannot load product ' . $item['id']); }
    $product->set_name($item['name'] . ' — ' . $item['label']['bs']);
    $product->set_status('draft'); // Publish only after every variation and image succeeds.
    $product->set_slug($item['id']);
    $product->set_description($item['description']['bs']);
    $product->set_short_description($item['label']['bs']);
    $product->update_meta_data('_slovenka_product', $item['id']);
    $product->update_meta_data('_slovenka_en', ['name' => $item['name'], 'label' => $item['label']['en'], 'description' => $item['description']['en']]);
    $product->save();
    $term = term_exists($item['type'], 'product_cat');
    if (!$term) {
        $category = array_values(array_filter($data['categories'], fn($c) => $c['id'] === $item['type']));
        $term = wp_insert_term($category[0]['bs'] ?? $item['type'], 'product_cat', ['slug' => $item['type']]);
    }
    if (is_wp_error($term)) { throw new RuntimeException($term->get_error_message()); }
    $product->set_category_ids([(int) (is_array($term) ? $term['term_id'] : $term)]);
    if ($draft) {
        $product->set_regular_price((string) $item['price']);
    } else {
        $attributes = [];
        foreach (['colour' => array_column($item['colors'], 'id'), 'size' => $item['sizes']] as $name => $options) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($name);
            $attribute->set_options($options);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $attributes[] = $attribute;
        }
        $product->set_attributes($attributes);
        $gallery = [];
        foreach ($item['colors'] as $color) {
            foreach (array_unique(array_merge([$color['cover']], $color['images'])) as $image) { $gallery[] = slovenka_media($data['images'][$image]['fallback']); }
        }
        $gallery = array_values(array_unique($gallery));
        $product->set_image_id($gallery[0]);
        $product->set_gallery_image_ids(array_slice($gallery, 1));
        $product->save();
        foreach ($item['colors'] as $color) {
            foreach ($item['sizes'] as $size) {
                slovenka_checkpoint();
                $key = $item['id'] . ':' . $color['id'] . ':' . $size;
                $variation_id = slovenka_find('product_variation', '_slovenka_variant', $key);
                $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();
                $variation->set_parent_id($product->get_id());
                $variation->set_attributes(['colour' => $color['id'], 'size' => $size]);
                $variation->set_regular_price((string) $item['price']);
                $variation->set_stock_status(in_array($size, $item['unavailable'], true) ? 'outofstock' : 'instock');
                $variation->set_image_id(slovenka_media($data['images'][$color['cover']]['fallback']));
                $variation->update_meta_data('_slovenka_variant', $key);
                $variation->save();
            }
        }
        $product->set_status('publish');
    }
    $product->update_meta_data('_slovenka_import_complete', 1);
    $product->save();
    if (!$draft) { WC_Product_Variable::sync($product->get_id()); }
}
