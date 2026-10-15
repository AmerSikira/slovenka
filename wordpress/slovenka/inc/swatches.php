<?php
if (!defined('ABSPATH')) { exit; }

// Saved attribute types and term metadata take precedence over legacy local defaults.
function slovenka_swatch_config($product, $attribute) {
    if (taxonomy_exists($attribute)) {
        $definition = wc_get_attribute(wc_attribute_taxonomy_id_by_name($attribute));
        $type = ['slovenka_color'=>'color', 'slovenka_size'=>'size'][$definition->type ?? ''] ?? '';
        $colors = [];
        if ($type === 'color') {
            foreach (wc_get_product_terms($product->get_id(), $attribute, ['fields'=>'all']) as $term) {
                $color = sanitize_hex_color(get_term_meta($term->term_id, '_slovenka_swatch_color', true));
                if ($color) { $colors[$term->slug] = $color; }
            }
        }
        return ['type'=>$type, 'colors'=>$colors];
    }
    $settings = $product->get_meta('_slovenka_swatch_settings');
    $key = sanitize_title($attribute);
    if (is_array($settings) && isset($settings[$key])) { return $settings[$key]; }
    // Compatibility for already-imported local attributes only. Saving the product
    // controls below replaces these defaults with explicit settings and shades.
    if (!$product->get_meta('_slovenka_product')) { return ['type'=>'', 'colors'=>[]]; }
    $type = in_array($key, ['color','colour','boja'], true) ? 'color' : (in_array($key, ['size','velicina'], true) ? 'size' : '');
    $palette = ['black'=>'#262622','navy'=>'#303744','green'=>'#1e5940','brown'=>'#79614c','olive'=>'#37392e','charcoal'=>'#55534f','burgundy'=>'#602d40','bronze'=>'#79614c'];
    $colors = [];
    $local = $product->get_attributes()[$key] ?? null;
    foreach ($local ? $local->get_options() : [] as $value) {
        if (isset($palette[strtolower($value)])) { $colors[$value] = $palette[strtolower($value)]; }
    }
    return ['type'=>$type, 'colors'=>$colors];
}
add_filter('woocommerce_dropdown_variation_attribute_options_html', function ($html, $args) {
    if (is_admin() || !is_product() || !($args['product'] instanceof WC_Product)) { return $html; }
    $config = slovenka_swatch_config($args['product'], $args['attribute']);
    if (!in_array($config['type'], ['color','size'], true)) { return $html; }
    $tag = new WP_HTML_Tag_Processor($html);
    if (!$tag->next_tag('select')) { return $html; }
    $tag->set_attribute('data-slovenka-swatches', $config['type']);
    if ($config['type'] === 'color') {
        $colors = array_filter(array_map('sanitize_hex_color', $config['colors']));
        $tag->set_attribute('data-swatch-colors', wp_json_encode((object)$colors));
    }
    return $tag->get_updated_html();
}, 20, 2);
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product() && wc_get_product(get_queried_object_id()) instanceof WC_Product_Variable) {
        wp_enqueue_script('slovenka-swatches', get_theme_file_uri('product-swatches.js'), ['wc-add-to-cart-variation'], wp_get_theme()->get('Version'), ['in_footer'=>true]);
    }
}, 110);
