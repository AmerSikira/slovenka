<?php
if (!defined('ABSPATH')) { exit; }
/** Legacy editorial product pages now lead to the product owned by WooCommerce. */
function slovenka_legacy_product($file) {
    $key = preg_replace('/-en$/', '', substr(pathinfo($file, PATHINFO_FILENAME), 8));
    $id = slovenka_find('product', '_slovenka_product', $key);
    if (!$id && function_exists('wc_get_product_id_by_sku')) { $id = wc_get_product_id_by_sku('slovenka-' . $key); }
    if (!$id) { $post = get_page_by_path($key, OBJECT, 'product'); $id = $post ? $post->ID : 0; }
    $product = $id && function_exists('wc_get_product') ? wc_get_product($id) : null;
    return $product && $product->is_visible() ? $product : null;
}
add_action('template_redirect', function () {
    if (!is_page()) { return; }
    $source = get_post_meta(get_queried_object_id(), '_slovenka_source', true);
    if (in_array($source, ['shop-en.html','cart-en.html','checkout-en.html','order.html','order-en.html'], true)) {
        if (function_exists('wc_get_page_permalink')) {
            $role = str_starts_with($source, 'shop') ? 'shop' : (str_starts_with($source, 'checkout') ? 'checkout' : 'cart');
            if ((int)wc_get_page_id($role) === get_queried_object_id()) { return; }
            $url = wc_get_page_permalink($role);
            if (!$url) { return; }
            foreach (WC()->query->get_query_vars() as $endpoint => $query_var) {
                $value = get_query_var($query_var, null);
                if ($value !== null) { $url = wc_get_endpoint_url($endpoint, $value, $url); break; }
            }
            $query = map_deep(wp_unslash($_GET), 'sanitize_text_field');
            unset($query['page_id'], $query['pagename']);
            wp_safe_redirect(add_query_arg($query, $url), 302); exit;
        }
        return;
    }
    if (!str_starts_with((string)$source, 'product-')) { return; }
    $product = slovenka_legacy_product($source);
    if ($product) {
        $url = $product->get_permalink();
        // Old bookmarked colour selectors map to the native WooCommerce attribute.
        $color = isset($_GET['color']) && is_string($_GET['color']) ? sanitize_text_field(wp_unslash($_GET['color'])) : '';
        foreach (['colour','color','pa_colour','pa_color','boja','pa_boja'] as $key) {
            if ($color !== '' && $product->get_attribute($key)) { $url = add_query_arg('attribute_' . sanitize_title($key), $color, $url); break; }
        }
        wp_safe_redirect($url, 302); exit;
    }
    global $wp_query;
    $wp_query->set_404(); status_header(404); nocache_headers();
}, 5);

add_filter('woocommerce_product_tabs', function ($tabs) {
    global $product;
    if ($product && mk_product_size_tables($product->get_id())) {
        $tabs['slovenka_measurements'] = ['title'=>'Tabela mjera / Size guide', 'priority'=>25, 'callback'=>'slovenka_measurements_tab'];
    }
    return $tabs;
});
function slovenka_measurements_tab() {
    global $product;
    foreach (mk_product_size_tables($product->get_id()) as $group) {
        echo '<h2>' . esc_html($group['name']) . '</h2>';
        foreach ($group['tables'] as $table) {
            echo '<div class="size-table-scroll" tabindex="0" role="region" aria-label="' . esc_attr($group['name']) . '"><table><thead><tr>';
            foreach ($table['headers'] as $header) { echo '<th scope="col">' . esc_html($header) . '</th>'; }
            echo '</tr></thead><tbody>';
            foreach ($table['rows'] as $row) {
                echo '<tr>';
                foreach ($row as $index => $value) { echo $index ? '<td>' . esc_html($value) . '</td>' : '<th scope="row">' . esc_html($value) . '</th>'; }
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
    }
}

// WooCommerce's own archive and single-product templates own the entire loop.
add_action('after_setup_theme', function () {
    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
    add_action('woocommerce_before_main_content', function () {
        echo '<main id="main" class="container section-space native-commerce' . (is_product() ? ' native-product' : '') . '">';
    }, 10);
    add_action('woocommerce_after_main_content', function () { echo '</main>'; }, 10);
});
