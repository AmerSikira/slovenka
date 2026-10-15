<?php
if (!defined('ABSPATH')) { exit; }

function slovenka_product_icon($name, $size = 16) {
    $paths = [
        'truck'=>'<path d="M10 17h4V5H2v12h3"/><path d="M14 9h4l4 4v4h-3"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/>',
        'return'=>'<path d="M3 11a9 9 0 1 1 2.6 6.4"/><path d="M3 4v7h7"/>',
        'layers'=>'<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>',
        'plus'=>'<path d="M5 12h14M12 5v14"/>',
    ];
    return '<svg aria-hidden="true" focusable="false" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? '') . '</svg>';
}
function slovenka_product_heading() {
    global $product;
    $parts = explode(' — ', $product->get_name(), 2);
    echo '<p class="eyebrow">MK SLOVENKA / ' . esc_html(slovenka_text('KOLEKCIJA','COLLECTION')) . '</p><h1 class="product_title entry-title">' . esc_html($parts[0]) . '</h1>';
    if (!empty($parts[1])) { echo '<p class="product-subtitle">' . esc_html($parts[1]) . '</p>'; }
}
function slovenka_product_price() {
    global $product;
    echo '<p class="price product-price">' . wp_kses_post($product->get_price_html()) . '<span class="product-price-label">' . esc_html(slovenka_text('Cijena','Price')) . '</span></p>';
}
function slovenka_product_intro() {
    global $product;
    $parts = explode(' — ', $product->get_name(), 2);
    $intro = $product->get_short_description();
    if (isset($parts[1]) && trim(wp_strip_all_tags($intro)) === trim($parts[1]) && $product->get_description()) { $intro = $product->get_description(); }
    $intro = apply_filters('woocommerce_short_description', $intro);
    if ($intro) { echo '<div class="woocommerce-product-details__short-description product-intro">' . $intro . '</div>'; }
}
function slovenka_product_benefits() {
    $benefits = apply_filters('slovenka_product_benefits', [
        ['truck', slovenka_text('Brza dostava (besplatna preko 100KM)', 'Fast delivery (free over 100KM)')],
        ['return', slovenka_text('15 dana povrat', '15-day returns')],
        ['layers', slovenka_text('Kvalitetni materijali', 'Quality materials')],
    ]);
    echo '<ul class="product-benefits">';
    foreach ($benefits as [$icon,$label]) { echo '<li class="product-benefit">' . slovenka_product_icon($icon) . '<span>' . esc_html($label) . '</span></li>'; }
    echo '</ul>';
}
function slovenka_size_guide_link() {
    return '<a class="underlined product-size-guide-trigger" href="#product-measurements">Tabela mjera</a>';
}
// The approved page opens on its first colour; an explicit merchant default or
// request selection still takes precedence. Sizes remain an explicit choice.
add_filter('woocommerce_dropdown_variation_attribute_options_args', function ($args) {
    $product = $args['product'] ?? null;
    $attribute = $args['attribute'] ?? '';
    if (is_admin() || !is_product() || !($product instanceof WC_Product_Variable) || !$attribute || (isset($args['selected']) && $args['selected'] !== false)) { return $args; }
    if (array_key_exists('attribute_' . sanitize_title($attribute), $_REQUEST) || $product->get_variation_default_attribute($attribute) !== '') { return $args; }
    if (slovenka_swatch_config($product, $attribute)['type'] === 'color') {
        $options = ($args['options'] ?? false) ?: ($product->get_variation_attributes()[$attribute] ?? []);
        if ($options) { $args['selected'] = reset($options); }
    }
    return $args;
});
add_filter('woocommerce_dropdown_variation_attribute_options_html', function ($html, $args) {
    if (is_admin() || !is_product() || !($args['product'] instanceof WC_Product)) { return $html; }
    return slovenka_swatch_config($args['product'], $args['attribute'])['type'] === 'size' ? slovenka_size_guide_link() . $html : $html;
}, 30, 2);
add_action('woocommerce_after_variations_table', function () {
    global $product;
    foreach ($product->get_variation_attributes() as $attribute=>$values) {
        if (slovenka_swatch_config($product, $attribute)['type'] === 'size') { return; }
    }
    echo '<p class="standalone-size-guide">' . slovenka_size_guide_link() . '</p>';
});
add_action('woocommerce_before_add_to_cart_form', function () {
    global $product;
    if (!$product->is_type('variable')) { echo '<p class="standalone-size-guide">' . slovenka_size_guide_link() . '</p>'; }
});
function slovenka_product_measurements() {
    global $product;
    $groups = mk_product_size_tables($product->get_id());
    echo '<section id="product-measurements" class="product-measurements-panel"><h2 id="product-measurements-title">Tabela mjera</h2><div class="modal-content size-guide-content"><p class="eyebrow">' . esc_html(str_replace(' — ', ' / ', $product->get_name())) . '</p><div class="product-measurement-charts">';
    if (!$groups) { echo '<p class="measurement-note">' . esc_html(slovenka_text('Tabela mjera za ovaj model još nije dostupna. Kontaktirajte nas za pomoć pri odabiru veličine.', 'Measurements for this model are not available yet. Contact us for help choosing a size.')) . '</p>'; }
    foreach ($groups as $group) {
        echo '<section class="measurement-chart"><h3>' . esc_html($group['name']) . '</h3>';
        foreach ($group['tables'] as $table) {
            echo '<div class="measurement-table" role="region" tabindex="0" aria-label="' . esc_attr($group['name'] . ' / ' . ($table['sheet'] ?? '')) . '"><table><thead><tr>';
            foreach ($table['headers'] as $header) { echo '<th scope="col">' . esc_html($header) . '</th>'; }
            echo '</tr></thead><tbody>';
            foreach ($table['rows'] as $row) {
                echo '<tr data-measurement-size="' . esc_attr($row[0] ?? '') . '">';
                foreach ($row as $index=>$value) { echo $index ? '<td>' . esc_html($value) . '</td>' : '<th scope="row">' . esc_html($value) . '</th>'; }
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '<p class="measurement-note">' . esc_html(slovenka_text('Mjere odjevnog predmeta u cm. Oznaka /2 predstavlja polovinu obima. Vrijednosti su prenesene iz tabele proizvođača.', "Garment measurements in cm. /2 indicates half the circumference. Values are taken from the manufacturer's table.")) . '</p></section>';
    }
    echo '</div><div class="measurement-instructions"><h3>' . esc_html(slovenka_text('Kako koristiti tabelu','How to use the table')) . '</h3><p>' . esc_html(slovenka_text('Uporedite mjere s odjevnim predmetom koji vam dobro pristaje. Obratite pažnju na oznake obima i poluobima (/2). Ako niste sigurni koju veličinu odabrati, kontaktirajte nas.', 'Compare these measurements with a garment that fits you well. Check whether each measurement is a full or half circumference (/2). Contact us for help choosing your size.')) . '</p></div>';
    $contact = slovenka_page_url('contact' . (slovenka_language() === 'en' ? '-en' : '') . '.html');
    if ($contact) { echo '<a class="text-link" href="' . esc_url($contact) . '">' . esc_html(slovenka_text('Zatražite informacije o modelu', 'Ask about this piece')) . ' ↗</a>'; }
    echo '</div></section>';
}
function slovenka_product_accordions() {
    global $product;
    $tabs = apply_filters('woocommerce_product_tabs', []);
    unset($tabs['slovenka_measurements']);
    echo '<div class="product-accordions">';
    $render = static function ($key, $tab) {
        $title = $key === 'description' ? slovenka_text('Kroj i detalji','Fit & details') : $tab['title'];
        echo '<details class="product-detail-' . esc_attr($key) . '"' . ($key === 'description' ? ' open' : '') . '><summary>' . wp_kses_post(apply_filters('woocommerce_product_' . $key . '_tab_title', $title, $key)) . slovenka_product_icon('plus') . '</summary><div class="product-detail-content" id="tab-' . esc_attr($key) . '">';
        if (isset($tab['callback']) && is_callable($tab['callback'])) { call_user_func($tab['callback'], $key, $tab); }
        echo '</div></details>';
    };
    if (isset($tabs['description'])) { $render('description', $tabs['description']); unset($tabs['description']); }
    echo '<details><summary>' . esc_html(slovenka_text('Sastav i održavanje','Composition & care')) . slovenka_product_icon('plus') . '</summary><p>' . esc_html(slovenka_text('Za informacije o sastavu i održavanju ovog modela, kontaktirajte nas.', 'Contact us for composition and care information for this piece.')) . '</p></details>';
    echo '<details><summary>' . esc_html(slovenka_text('Dostava i povrat','Delivery & returns')) . slovenka_product_icon('plus') . '</summary><p>';
    foreach (['support-delivery'=>['Dostava','Delivery'], 'support-returns'=>['Povrat','Returns']] as $file=>$labels) {
        $url = slovenka_page_url($file . (slovenka_language() === 'en' ? '-en' : '') . '.html');
        if ($url) { echo '<a class="underlined" href="' . esc_url($url) . '">' . esc_html(slovenka_text(...$labels)) . '</a> '; }
    }
    echo '</p></details>';
    foreach ($tabs as $key=>$tab) { $render($key, $tab); }
    echo '</div>';
    do_action('woocommerce_product_after_tabs');
}
add_action('after_setup_theme', function () {
    remove_action('woocommerce_single_product_summary','woocommerce_template_single_title',5);
    remove_action('woocommerce_single_product_summary','woocommerce_template_single_price',10);
    remove_action('woocommerce_single_product_summary','woocommerce_template_single_excerpt',20);
    remove_action('woocommerce_after_single_product_summary','woocommerce_output_product_data_tabs',10);
    add_action('woocommerce_before_single_product_summary', function () { echo '<div class="product-layout native-product-layout">'; }, 1);
    add_action('woocommerce_after_single_product_summary', function () { echo '</div>'; }, 1);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    add_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 70);
    add_action('woocommerce_single_product_summary','slovenka_product_heading',5);
    add_action('woocommerce_single_product_summary','slovenka_product_price',10);
    add_action('woocommerce_single_product_summary','slovenka_product_intro',20);
    add_action('woocommerce_single_product_summary','slovenka_product_benefits',35);
    add_action('woocommerce_single_product_summary','slovenka_product_accordions',60);
    add_action('woocommerce_after_single_product_summary','slovenka_product_measurements',10);
});
add_filter('woocommerce_product_description_heading', '__return_empty_string');
add_filter('woocommerce_product_additional_information_heading', '__return_empty_string');
add_filter('woocommerce_product_single_add_to_cart_text', function ($text, $product) {
    return $product->is_type(['simple','variable']) ? slovenka_text('Dodaj u korpu','Add to bag') : $text;
}, 10, 2);
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product()) { wp_enqueue_script('slovenka-product-design', get_theme_file_uri('product-design.js'), [], wp_get_theme()->get('Version'), ['in_footer'=>true]); }
}, 110);

// Keep unavailable sizes crossed out using WooCommerce's own option matching.
add_filter('woocommerce_variation_is_active', function ($active, $variation) {
    return !is_admin() && is_product() ? $active && $variation->is_in_stock() : $active;
}, 20, 2);
add_filter('woocommerce_attribute_label', function ($label, $name, $target) {
    $target = $target instanceof WC_Product ? $target : ($GLOBALS['product'] ?? null);
    if (is_admin() || !is_product() || !($target instanceof WC_Product) || !$target->get_meta('_slovenka_product') || taxonomy_exists($name)) { return $label; }
    return ['colour'=>slovenka_text('Boja','Colour'),'color'=>slovenka_text('Boja','Colour'),'size'=>slovenka_text('Veličina','Size')][$label] ?? $label;
}, 20, 3);
