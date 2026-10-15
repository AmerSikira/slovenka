<?php
if (!defined('ABSPATH')) { exit; }

// WooCommerce persists these types in its native attribute definition table.
add_filter('product_attributes_type_selector', function ($types) {
    $types['slovenka_color'] = 'Boja / Colour';
    $types['slovenka_size'] = 'Veličina / Size';
    return $types;
});
function slovenka_is_color_taxonomy($taxonomy) {
    $attribute = function_exists('wc_get_attribute') ? wc_get_attribute(wc_attribute_taxonomy_id_by_name($taxonomy)) : null;
    return $attribute && $attribute->type === 'slovenka_color';
}
function slovenka_color_input($name, $value, $id) {
    return '<input type="text" class="slovenka-color-field optional_attribute_or_variation_data" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="' . esc_attr(sanitize_hex_color($value) ?: '') . '" placeholder="#1e5940" pattern="#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" aria-label="Nijansa / Shade">';
}
function slovenka_save_term_color($term_id, $taxonomy) {
    $nonce = $_POST['slovenka_term_color_nonce'] ?? '';
    $value = $_POST['slovenka_term_color'] ?? null;
    $term = get_term($term_id, $taxonomy);
    if (!$term || is_wp_error($term) || !slovenka_is_color_taxonomy($taxonomy) || !current_user_can('edit_term', $term_id)
        || !is_string($nonce) || !wp_verify_nonce(wp_unslash($nonce), 'slovenka_term_color_' . $taxonomy) || !is_string($value)) { return; }
    $value = trim(wp_unslash($value));
    if ($value === '') { delete_term_meta($term_id, '_slovenka_swatch_color'); return; }
    $color = sanitize_hex_color($value);
    if ($color) { update_term_meta($term_id, '_slovenka_swatch_color', $color); }
    // Invalid input never replaces a previously saved shade.
}
add_action('init', function () {
    if (!function_exists('wc_get_attribute_taxonomies')) { return; }
    foreach (wc_get_attribute_taxonomies() as $attribute) {
        if ($attribute->attribute_type !== 'slovenka_color') { continue; }
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        add_action($taxonomy . '_add_form_fields', function () use ($taxonomy) {
            echo '<div class="form-field"><label for="slovenka-term-color">Nijansa / Shade</label>' . slovenka_color_input('slovenka_term_color', '', 'slovenka-term-color') . '<p>Odaberite stvarnu nijansu ili unesite HEX kod. / Pick the actual shade or enter a HEX code.</p>';
            wp_nonce_field('slovenka_term_color_' . $taxonomy, 'slovenka_term_color_nonce', false);
            echo '</div>';
        });
        add_action($taxonomy . '_edit_form_fields', function ($term) use ($taxonomy) {
            echo '<tr class="form-field"><th scope="row"><label for="slovenka-term-color">Nijansa / Shade</label></th><td>' . slovenka_color_input('slovenka_term_color', get_term_meta($term->term_id, '_slovenka_swatch_color', true), 'slovenka-term-color') . '<p class="description">Odaberite stvarnu nijansu ili unesite HEX kod. / Pick the actual shade or enter a HEX code.</p>';
            wp_nonce_field('slovenka_term_color_' . $taxonomy, 'slovenka_term_color_nonce', false);
            echo '</td></tr>';
        });
        foreach (['created_', 'edited_'] as $event) {
            add_action($event . $taxonomy, function ($term_id) use ($taxonomy) { slovenka_save_term_color($term_id, $taxonomy); });
        }
        add_filter('manage_edit-' . $taxonomy . '_columns', function ($columns) { $columns['slovenka_shade'] = 'Nijansa / Shade'; return $columns; });
        add_filter('manage_' . $taxonomy . '_custom_column', function ($content, $column, $term_id) {
            if ($column !== 'slovenka_shade') { return $content; }
            $color = sanitize_hex_color(get_term_meta($term_id, '_slovenka_swatch_color', true));
            return $color ? '<span aria-hidden="true" style="display:inline-block;width:24px;height:24px;vertical-align:middle;border:1px solid #888;border-radius:50%;background:' . esc_attr($color) . '"></span> ' . esc_html($color) : '—';
        }, 10, 3);
    }
}, 20);

// WooCommerce asks custom attribute types to provide their product-editor term UI.
add_action('woocommerce_product_option_terms', function ($taxonomy, $index, $attribute) {
    if (!$taxonomy || !in_array($taxonomy->attribute_type, ['slovenka_color','slovenka_size'], true)) { return; }
    echo '<select multiple="multiple" class="multiselect attribute_values wc-taxonomy-term-search" name="attribute_values[' . esc_attr($index) . '][]" data-taxonomy="' . esc_attr($attribute->get_name()) . '" data-return_id="id" data-limit="50" data-minimum_input_length="0" data-orderby="' . esc_attr($taxonomy->attribute_orderby) . '" data-placeholder="' . esc_attr__('Select values', 'woocommerce') . '">';
    foreach ($attribute->get_terms() ?: [] as $term) { echo '<option value="' . esc_attr($term->term_id) . '" selected>' . esc_html($term->name) . '</option>'; }
    echo '</select><button class="button plus select_all_attributes">' . esc_html__('Select all', 'woocommerce') . '</button> <button class="button minus select_no_attributes">' . esc_html__('Select none', 'woocommerce') . '</button> <button class="button fr plus add_new_attribute">' . esc_html__('Create value', 'woocommerce') . '</button>';
}, 10, 3);

// Local attributes have no shared terms; keep editable shades beside their values.
add_action('woocommerce_after_product_attribute_settings', function ($attribute, $index) {
    if ($attribute->is_taxonomy()) { return; }
    global $product_object;
    $product = $product_object instanceof WC_Product ? $product_object : wc_get_product(absint($_POST['post_id'] ?? get_the_ID()));
    if (!$product) { return; }
    $config = slovenka_swatch_config($product, $attribute->get_name());
    echo '<tr class="slovenka-local-swatches"><td colspan="2"><label>Prikaz / Display <select class="slovenka-swatch-type optional_attribute_or_variation_data" name="slovenka_swatches[' . esc_attr($index) . '][type]">';
    foreach ([''=>'Padajuća lista / Dropdown', 'color'=>'Boja / Colour', 'size'=>'Veličina / Size'] as $type=>$label) { echo '<option value="' . esc_attr($type) . '"' . selected($config['type'], $type, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select></label><div class="slovenka-local-colors"' . ($config['type'] === 'color' ? '' : ' hidden') . '>';
    foreach (array_values($attribute->get_options()) as $position=>$value) {
        $base = 'slovenka_swatches[' . $index . '][colors][' . $position . ']';
        $id = 'slovenka-local-color-' . $index . '-' . $position;
        echo '<p><label for="' . esc_attr($id) . '">' . esc_html($value) . '</label><input type="hidden" name="' . esc_attr($base . '[value]') . '" value="' . esc_attr($value) . '"> ' . slovenka_color_input($base . '[color]', $config['colors'][$value] ?? '', $id) . '</p>';
    }
    echo '<p class="description">Nakon promjene vrijednosti kliknite Sačuvaj atribute za prikaz nijansi. / After editing values, save attributes to refresh the shade fields.</p></div>';
    wp_nonce_field('slovenka_product_swatches_' . $product->get_id(), 'slovenka_swatches_nonce', false);
    echo '</td></tr>';
}, 10, 2);
add_action('woocommerce_before_product_object_save', function ($product) {
    if (!is_admin() || !$product->get_id() || !current_user_can('edit_post', $product->get_id())) { return; }
    $data = $_POST;
    if (isset($_POST['data']) && is_string($_POST['data'])) { parse_str(wp_unslash($_POST['data']), $data); }
    else { $data = wp_unslash($data); }
    $nonce = $data['slovenka_swatches_nonce'] ?? '';
    if (!is_string($nonce) || !wp_verify_nonce($nonce, 'slovenka_product_swatches_' . $product->get_id()) || !is_array($data['slovenka_swatches'] ?? null) || !is_array($data['attribute_names'] ?? null)) { return; }
    $settings = $product->get_meta('_slovenka_swatch_settings');
    $settings = is_array($settings) ? $settings : [];
    foreach ($data['slovenka_swatches'] as $index=>$config) {
        if (!is_array($config) || !is_string($data['attribute_names'][$index] ?? null) || !in_array($config['type'] ?? null, ['', 'color', 'size'], true)) { continue; }
        $key = sanitize_title($data['attribute_names'][$index]);
        $attribute = $product->get_attributes()[$key] ?? null;
        if (!$attribute || $attribute->is_taxonomy()) { continue; }
        $old = slovenka_swatch_config($product, $attribute->get_name());
        $colors = array_intersect_key($old['colors'], array_flip($attribute->get_options()));
        foreach (is_array($config['colors'] ?? null) ? $config['colors'] : [] as $entry) {
            if (!is_array($entry) || !is_string($entry['value'] ?? null) || !in_array($entry['value'], $attribute->get_options(), true) || !is_string($entry['color'] ?? null)) { continue; }
            $value = trim($entry['color']);
            $color = sanitize_hex_color($value);
            if ($color) { $colors[$entry['value']] = $color; }
            elseif ($value === '') { unset($colors[$entry['value']]); }
        }
        $settings[$key] = ['type'=>$config['type'], 'colors'=>$colors];
    }
    $settings = array_intersect_key($settings, $product->get_attributes());
    $product->update_meta_data('_slovenka_swatch_settings', $settings);
});
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if (!$screen || !($screen->post_type === 'product' && in_array($screen->base, ['post','term','edit-tags'], true))) { return; }
    if ($screen->base !== 'post' && !slovenka_is_color_taxonomy($screen->taxonomy)) { return; }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('slovenka-swatch-admin', get_theme_file_uri('swatch-admin.js'), ['wp-color-picker'], wp_get_theme()->get('Version'), ['in_footer'=>true]);
});
