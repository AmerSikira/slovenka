<?php
/**
 * Plugin Name: Slovenka Catalogue Import
 * Description: Optional, manually run catalogue import. Products and photos are stored in WooCommerce and Media, independently of the theme.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 * Requires PHP: 8.1
 */
if (!defined('ABSPATH')) { exit; }
function slovenka_catalogue_data() {
    static $data;
    return $data ??= json_decode(file_get_contents(__DIR__ . '/data/catalogue.json'), true, 512, JSON_THROW_ON_ERROR);
}
function slovenka_catalogue_find($type, $key, $value) {
    $ids = get_posts(['post_type' => $type, 'post_status' => 'any', 'meta_key' => $key, 'meta_value' => $value, 'fields' => 'ids', 'posts_per_page' => 1]);
    return $ids ? (int) $ids[0] : 0;
}
function slovenka_catalogue_checkpoint() {
    if (microtime(true) > ($GLOBALS['slovenka_catalogue_deadline'] ?? PHP_FLOAT_MAX)) { throw new RuntimeException('__continue__'); }
}
function slovenka_catalogue_media($path) {
    $aliases = slovenka_catalogue_data()['image_aliases'] ?? [];
    $path = $aliases[$path] ?? $path;
    $id = slovenka_catalogue_find('attachment', '_slovenka_asset', $path);
    if ($id) { return $id; }
    // Reuse the attachment imported by an older theme instead of uploading a second copy.
    foreach ($aliases as $legacy => $current) {
        if ($current !== $path) { continue; }
        $id = slovenka_catalogue_find('attachment', '_slovenka_asset', $legacy);
        if ($id) {
            update_post_meta($id, '_slovenka_asset', $path);
            return $id;
        }
    }
    slovenka_catalogue_checkpoint();
    $file = __DIR__ . '/' . $path;
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
function slovenka_catalogue_import_product($item, $data) {
    $id = slovenka_catalogue_find('product', '_slovenka_product', $item['id']);
    $sku = 'slovenka-' . $item['id'];
    if (!$id) { $id = wc_get_product_id_by_sku($sku); }
    if ($id && !get_post_meta($id, '_slovenka_import_pending', true)) {
        $existing = wc_get_product($id);
        if ($existing && !$existing->get_sku() && !wc_get_product_id_by_sku($sku)) { $existing->set_sku($sku); $existing->save(); }
        foreach ($existing ? $existing->get_children() : [] as $child_id) {
            $key = get_post_meta($child_id, '_slovenka_variant', true);
            $child = wc_get_product($child_id);
            $child_sku = 'slovenka-' . str_replace(':', '-', $key);
            if ($key && $child && !$child->get_sku() && !wc_get_product_id_by_sku($child_sku)) { $child->set_sku($child_sku); $child->save(); }
        }
        return;
    }
    // A matching externally imported product is already owned by WooCommerce.
    if ($id && !metadata_exists('post', $id, '_slovenka_product')) { return; }
    $draft = ($item['status'] ?? '') === 'draft';
    $product = $id ? wc_get_product($id) : ($draft ? new WC_Product_Simple() : new WC_Product_Variable());
    if (!$product) { throw new RuntimeException('Cannot load product ' . $item['id']); }
    if (!$product->get_sku()) { $product->set_sku($sku); }
    $product->set_name($item['name'] . ' — ' . $item['label']['bs']);
    $product->set_status('draft'); // Publish only after every variation and image succeeds.
    $product->set_slug($item['id']);
    $product->set_description($item['description']['bs']);
    $product->set_short_description($item['label']['bs']);
    $product->update_meta_data('_slovenka_product', $item['id']);
    $product->update_meta_data('_slovenka_import_pending', 1);
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
            foreach (array_unique(array_merge([$color['cover']], $color['images'])) as $image) { $gallery[] = slovenka_catalogue_media($data['images'][$image]['fallback']); }
        }
        $gallery = array_values(array_unique($gallery));
        $product->set_image_id($gallery[0]);
        $product->set_gallery_image_ids(array_slice($gallery, 1));
        $product->save();
        foreach ($item['colors'] as $color) {
            foreach ($item['sizes'] as $size) {
                slovenka_catalogue_checkpoint();
                $key = $item['id'] . ':' . $color['id'] . ':' . $size;
                $variation_id = slovenka_catalogue_find('product_variation', '_slovenka_variant', $key);
                $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();
                if (!$variation->get_sku()) { $variation->set_sku('slovenka-' . str_replace(':', '-', $key)); }
                $variation->set_parent_id($product->get_id());
                $variation->set_attributes(['colour' => $color['id'], 'size' => $size]);
                $variation->set_regular_price((string) $item['price']);
                $variation->set_stock_status(in_array($size, $item['unavailable'], true) ? 'outofstock' : 'instock');
                $variation->set_image_id(slovenka_catalogue_media($data['images'][$color['cover']]['fallback']));
                $variation->update_meta_data('_slovenka_variant', $key);
                $variation->save();
            }
        }
        $product->set_status('publish');
    }
    $product->update_meta_data('_slovenka_import_complete', 1);
    $product->delete_meta_data('_slovenka_import_pending');
    $product->save();
    if (!$draft) { WC_Product_Variable::sync($product->get_id()); }
}

/** Import tables only when the optional theme's size-table editor is available. */
function slovenka_catalogue_import_tables($data) {
    if (!post_type_exists('mk_size_table')) { return; }
    foreach ($data['size_tables'] ?? [] as $seed) {
        $id = slovenka_catalogue_find('mk_size_table', '_slovenka_size_seed', $seed['key']);
        if (!$id) { $id = slovenka_catalogue_find('mk_size_table', '_mk_size_filename', $seed['filename']); }
        if (!$id) {
            $id = wp_insert_post(wp_slash(['post_type'=>'mk_size_table', 'post_status'=>'publish', 'post_title'=>$seed['name'], 'meta_input'=>['_mk_size_tables'=>$seed['tables'], '_mk_size_filename'=>$seed['filename'], '_slovenka_size_seed'=>$seed['key']]]), true);
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
        }
        foreach ($seed['products'] as $key) {
            $product_id = slovenka_catalogue_find('product', '_slovenka_product', $key) ?: wc_get_product_id_by_sku('slovenka-' . $key);
            if ($product_id && !metadata_exists('post', $product_id, '_mk_size_table_ids')) { update_post_meta($product_id, '_mk_size_table_ids', [(int)$id]); }
        }
    }
}

// Nothing imports on activation, page load, cron or theme updates.
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=product', 'Slovenka product import', 'Slovenka import', 'manage_woocommerce', 'slovenka-catalogue-import', 'slovenka_catalogue_import_screen');
});
function slovenka_catalogue_import_screen() {
    if (!current_user_can('manage_woocommerce')) { return; }
    echo '<div class="wrap"><h1>Slovenka product import</h1><p>Import the starter catalogue and photographs into WooCommerce and the Media Library. Existing products are preserved. Blank SKUs on products from the old theme receive stable SKUs for future CSV matching.</p><p>This is a manual, optional import. You can deactivate and delete this plugin afterwards. Product edits and new products are managed under Products.</p><button type="button" class="button button-primary" id="slovenka-run-import">Import missing products</button><p id="slovenka-import-status" role="status" aria-live="polite"></p></div>';
    $config = wp_json_encode(['url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('slovenka-catalogue-import')]);
    echo '<script>
    document.getElementById("slovenka-run-import").addEventListener("click", async function () {
        this.disabled = true;
        const status = document.getElementById("slovenka-import-status");
        const config = ' . $config . ';
        try {
            let done = false;
            while (!done) {
                status.textContent = "Importing missing products and photographs… Keep this page open.";
                const response = await fetch(config.url, {method:"POST", credentials:"same-origin", body:new URLSearchParams({action:"slovenka_catalogue_import",nonce:config.nonce})});
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.data?.message || "Import failed. You can retry safely.");
                done = result.data.done;
                status.textContent = result.data.message;
            }
        } catch (error) { status.textContent = error.message; }
        finally { this.disabled = false; }
    });</script>';
}
add_action('wp_ajax_slovenka_catalogue_import', function () {
    if (!current_user_can('manage_woocommerce')) { wp_send_json_error(['message'=>'Not permitted.'], 403); }
    check_ajax_referer('slovenka-catalogue-import', 'nonce');
    if (!class_exists('WC_Product_Variable')) { wp_send_json_error(['message'=>'Activate WooCommerce first.'], 400); }
    if ((int)get_option('slovenka_catalogue_import_lock') < time() - 300) { delete_option('slovenka_catalogue_import_lock'); }
    if (!add_option('slovenka_catalogue_import_lock', time(), '', false)) { wp_send_json_error(['message'=>'An import is already running. Retry in a moment.'], 409); }
    $done = true; $error = '';
    $GLOBALS['slovenka_catalogue_deadline'] = microtime(true) + 8;
    try {
        $data = slovenka_catalogue_data();
        foreach ($data['products'] as $item) { slovenka_catalogue_checkpoint(); slovenka_catalogue_import_product($item, $data); }
        slovenka_catalogue_import_tables($data);
    } catch (Throwable $exception) {
        if ($exception->getMessage() === '__continue__') { $done = false; }
        else { $error = $exception->getMessage(); }
    } finally { delete_option('slovenka_catalogue_import_lock'); }
    if ($error) { wp_send_json_error(['message'=>$error . ' You can retry safely.'], 400); }
    wp_send_json_success(['done'=>$done, 'message'=>$done ? 'Import complete. Manage your catalogue under Products. You may deactivate and delete this plugin.' : 'Batch complete; continuing…']);
});
