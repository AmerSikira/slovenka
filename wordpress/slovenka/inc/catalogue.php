<?php
/** Presentation adapter: WooCommerce and Media are the only product data sources. */
if (!defined('ABSPATH')) { exit; }
function slovenka_catalogue() {
    static $cached;
    if ($cached !== null) { return $cached; }
    $result = ['products'=>[], 'images'=>[], 'categories'=>[]];
    if (!function_exists('wc_get_products')) { return $cached = $result; }
    $image = function ($id) use (&$result) {
        $key = 'media-' . (int)$id;
        if (!isset($result['images'][$key])) {
            $full = $id ? wp_get_attachment_image_src($id, 'full') : false;
            $result['images'][$key] = ['src'=>$full ? $full[0] : wc_placeholder_img_src(), 'fallback'=>$full ? $full[0] : wc_placeholder_img_src(), 'width'=>$full ? $full[1] : 800, 'height'=>$full ? $full[2] : 1000, 'srcSet'=>$id ? (wp_get_attachment_image_srcset($id, 'full') ?: '') : ''];
        }
        return $key;
    };
    $terms = get_terms(['taxonomy'=>'product_cat', 'hide_empty'=>true]);
    foreach (is_wp_error($terms) ? [] : $terms as $term) {
        $result['categories'][] = ['id'=>$term->slug, 'bs'=>$term->name, 'en'=>$term->name, 'image'=>$image((int)get_term_meta($term->term_id, 'thumbnail_id', true))];
    }
    foreach (wc_get_products(['status'=>'publish', 'limit'=>-1, 'orderby'=>'menu_order', 'order'=>'ASC']) as $product) {
        if (!$product->is_visible()) { continue; }
        $attributes = $product->get_attributes();
        $colour_key = ''; $size_key = '';
        foreach ($attributes as $key => $attribute) {
            $name = preg_replace('/^pa_/', '', sanitize_title($key));
            if (in_array($name, ['colour','color','boja'], true)) { $colour_key = $key; }
            if (in_array($name, ['size','velicina'], true)) { $size_key = $key; }
        }
        $sizes = $size_key ? array_map('strval', $attributes[$size_key]->is_taxonomy() ? wp_list_pluck($attributes[$size_key]->get_terms(), 'name') : $attributes[$size_key]->get_options()) : [];
        $gallery = array_values(array_unique(array_filter(array_merge([$product->get_image_id()], $product->get_gallery_image_ids()))));
        $colors = []; $unavailable = []; $available_sizes = [];
        foreach ($product->is_type('variable') ? $product->get_children() : [] as $child_id) {
            $variation = wc_get_product($child_id);
            if (!$variation || $variation->get_status() !== 'publish') { continue; }
            $value = $colour_key ? $variation->get_attributes()[$colour_key] ?? '' : '';
            $values = [$value];
            if ($colour_key && $value === '') {
                $attribute = $attributes[$colour_key];
                $values = $attribute->is_taxonomy() ? wp_list_pluck($attribute->get_terms(), 'slug') : $attribute->get_options();
            }
            foreach ($values as $value) {
                $term = $colour_key && taxonomy_exists($colour_key) ? get_term_by('slug', $value, $colour_key) : null;
                $name = $term ? $term->name : $value;
                $hex = $term ? sanitize_hex_color(get_term_meta($term->term_id, 'product_attribute_color', true)) : '';
                // Colour names are presentation hints only; arbitrary attributes still work.
                $palette = ['black'=>'#262622','navy'=>'#303744','green'=>'#246a4d','brown'=>'#79614c','olive'=>'#49493d','charcoal'=>'#55534f','burgundy'=>'#74404b','bronze'=>'#79614c','white'=>'#f1f0ec'];
                $cover = $variation->get_image_id() ?: $product->get_image_id();
                if (!isset($colors[$value])) {
                    $colors[$value] = ['id'=>(string)$value, 'name'=>['bs'=>$name, 'en'=>$name], 'hex'=>$hex ?: ($palette[$value] ?? '#b7b4ac'), 'cover'=>$image($cover), 'images'=>array_map($image, array_values(array_unique(array_merge([$cover], $gallery))))];
                }
            }
            if ($variation->is_purchasable() && $variation->is_in_stock()) {
                $size = $size_key ? $variation->get_attributes()[$size_key] ?? '' : '';
                $size_term = $size_key && taxonomy_exists($size_key) ? get_term_by('slug', $size, $size_key) : null;
                if ($size === '') { $available_sizes = array_merge($available_sizes, $sizes); }
                else { $available_sizes[] = $size_term ? $size_term->name : $size; }
            }
        }
        if (!$colors) { $colors[] = ['id'=>'', 'name'=>['bs'=>'','en'=>''], 'hex'=>'#b7b4ac', 'cover'=>$image($product->get_image_id()), 'images'=>array_map($image, $gallery)]; }
        if ($product->is_type('variable')) { $unavailable = array_values(array_diff($sizes, $available_sizes)); }
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields'=>'slugs']);
        $categories = is_wp_error($categories) ? [] : $categories;
        $name = wp_specialchars_decode($product->get_name(), ENT_QUOTES);
        $label = wp_specialchars_decode(wp_strip_all_tags($product->get_short_description()), ENT_QUOTES);
        $result['products'][] = ['id'=>(string)$product->get_id(), 'name'=>$name, 'label'=>['bs'=>$label,'en'=>$label], 'type'=>$categories[0] ?? '', 'categories'=>$categories, 'colourAttribute'=>$colour_key, 'price'=>(float)wc_get_price_to_display($product), 'priceHtml'=>wp_kses_post($product->get_price_html()), 'permalink'=>$product->get_permalink(), 'colors'=>array_values($colors), 'sizes'=>$sizes, 'unavailable'=>$unavailable, 'isNew'=>$product->get_date_created() && $product->get_date_created()->getTimestamp() >= time() - 60 * DAY_IN_SECONDS, 'inStock'=>$product->is_in_stock()];
    }
    return $cached = $result;
}

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
