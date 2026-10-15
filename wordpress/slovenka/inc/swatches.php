<?php
if (!defined('ABSPATH')) { exit; }

// Enhance the native controls without replacing WooCommerce's variation form.
add_filter('woocommerce_dropdown_variation_attribute_options_html', function ($html, $args) {
    if (is_admin() || !is_product()) { return $html; }
    $attribute = preg_replace('/^pa_/', '', sanitize_title($args['attribute']));
    $type = in_array($attribute, ['color', 'colour', 'boja'], true) ? 'color' : (in_array($attribute, ['size', 'velicina'], true) ? 'size' : '');
    if (!$type) { return $html; }
    $tag = new WP_HTML_Tag_Processor($html);
    if (!$tag->next_tag('select')) { return $html; }
    $tag->set_attribute('data-slovenka-swatches', $type);
    if ($type === 'color') {
        // These shades match the original catalogue. Unknown shades use labelled
        // buttons rather than guessing a colour. Custom palettes can use this filter.
        $colors = apply_filters('slovenka_swatch_colors', [
            'black'=>'#262622', 'crna'=>'#262622', 'navy'=>'#303744', 'tamnoplava'=>'#303744',
            'green'=>'#1e5940', 'zelena'=>'#1e5940', 'brown'=>'#79614c', 'smeda'=>'#79614c',
            'olive'=>'#37392e', 'tamna-maslina'=>'#37392e', 'charcoal'=>'#55534f', 'tamnosiva'=>'#55534f',
            'burgundy'=>'#602d40', 'bordo'=>'#602d40', 'bronze'=>'#79614c', 'bronzana'=>'#79614c',
            'white'=>'#ffffff', 'bijela'=>'#ffffff', 'beige'=>'#d4c3a5', 'bez'=>'#d4c3a5',
            'grey'=>'#8a8a86', 'gray'=>'#8a8a86', 'siva'=>'#8a8a86', 'blue'=>'#315b85', 'plava'=>'#315b85',
        ], $args['product']);
        $colors = array_filter(array_map('sanitize_hex_color', $colors));
        $tag->set_attribute('data-swatch-colors', wp_json_encode($colors));
    }
    return $tag->get_updated_html();
}, 20, 2);
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product() && wc_get_product(get_queried_object_id()) instanceof WC_Product_Variable) {
        wp_enqueue_script('slovenka-swatches', get_theme_file_uri('product-swatches.js'), ['wc-add-to-cart-variation'], wp_get_theme()->get('Version'), ['in_footer'=>true]);
    }
}, 110);
