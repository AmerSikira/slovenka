<?php
if (!defined('ABSPATH')) { exit; }
function slovenka_page() {
    $file = get_post_meta(get_queried_object_id(), '_slovenka_source', true);
    return slovenka_data()['pages'][$file] ?? null;
}
function slovenka_urls() {
    $urls = [];
    foreach (get_option('slovenka_pages', []) as $file => $id) { $urls[$file] = get_permalink($id); }
    if (function_exists('wc_get_cart_url')) {
        foreach (['cart.html', 'cart-en.html'] as $file) { $urls[$file] = wc_get_cart_url(); }
        foreach (['checkout.html', 'checkout-en.html'] as $file) { $urls[$file] = wc_get_checkout_url(); }
    }
    return $urls;
}
function slovenka_html($html) {
    // Older imported pages may still reference the previous JPEG fallback names.
    $html = strtr($html, slovenka_data()['image_aliases'] ?? []);
    $base = get_template_directory_uri() . '/';
    $urls = slovenka_urls();
    // Replace only known local documents; preserve queries, anchors and external URLs.
    $html = preg_replace_callback('~\b(href|action)=(["\'])([^"\']+)\2~', function ($match) use ($urls) {
        $parts = preg_split('/(?=[?#])/', $match[3], 2);
        if (!isset($urls[$parts[0]])) { return $match[0]; }
        $url = $urls[$parts[0]];
        $suffix = $parts[1] ?? '';
        if (str_starts_with($suffix, '?') && str_contains($url, '?')) { $suffix = '&' . substr($suffix, 1); }
        return $match[1] . '=' . $match[2] . esc_url($url . $suffix) . $match[2];
    }, $html);
    $html = preg_replace_callback('~(?<=["\'\s(,])(assets/[^"\'\s),]+|styles\.css(?:\?[^"\'\s]+)?|(?:script|motion|contact-map)\.js(?:\?[^"\'\s]+)?)~', fn($m) => esc_url($base . $m[1]), $html);
    return $html;
}
function slovenka_catalogue() {
    static $cached;
    if ($cached !== null) { return $cached; }
    $products = []; $variations = [];
    foreach (slovenka_data()['products'] as $item) {
        if (($item['status'] ?? '') === 'draft') { continue; }
        $id = slovenka_find('product', '_slovenka_product', $item['id']);
        $product = $id && function_exists('wc_get_product') ? wc_get_product($id) : null;
        if (function_exists('wc_get_product') && (!$product || $product->get_status() !== 'publish')) { continue; }
        if ($product) {
            $price = $product->get_price();
            if ($price !== '') { $item['price'] = (float) wc_get_price_to_display($product); }
            $item['sizes'] = array_map('strval', $product->get_attribute('size') ? array_map('trim', explode('|', $product->get_attribute('size'))) : $item['sizes']);
            $item['unavailable'] = [];
            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation || !$variation->is_purchasable() || !$variation->is_in_stock()) { continue; }
                $variations[$item['id']][] = ['id' => $variation_id, 'product' => $id, 'color' => $variation->get_attribute('colour'), 'size' => $variation->get_attribute('size'), 'price' => (float) wc_get_price_to_display($variation)];
            }
            foreach ($item['sizes'] as $size) {
                if (!array_filter($variations[$item['id']] ?? [], fn($v) => $v['size'] === $size)) { $item['unavailable'][] = $size; }
            }
        }
        $products[] = $item;
    }
    return $cached = ['products' => $products, 'variations' => $variations];
}
function slovenka_config() {
    $catalogue = slovenka_catalogue();
    return array_merge($catalogue, ['urls' => slovenka_urls(), 'assetBase' => get_template_directory_uri() . '/', 'production' => true, 'cart' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '', 'cartCount' => function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0]);
}
function slovenka_content() {
    $page = slovenka_page();
    if (!$page) {
        if (function_exists('is_woocommerce') && is_woocommerce()) { woocommerce_content(); return; }
        while (have_posts()) { the_post(); the_title('<h1>', '</h1>'); the_content(); }
        return;
    }
    while (have_posts()) { the_post();
        // WooCommerce owns checkout endpoints, customer data, validation, totals and orders.
        if (in_array($page['route'], ['/cart', '/checkout'], true)) {
            echo '<section class="container section-space">';
            if (class_exists('WooCommerce')) { the_content(); }
            else { echo '<p>Prodavnica se priprema. Molimo kontaktirajte nas za narudžbu. / The shop is being prepared. Please contact us to order.</p>'; }
            echo '</section>';
        } else {
            $content = get_the_content();
            if ($page['route'] === '/shop' || str_starts_with($page['route'], '/product/')) {
                $content = preg_replace('~<p class="(?:catalog-note|preview-helper)">[\s\S]*?</p>~', '', $content);
            }
            $catalogue = slovenka_catalogue();
            $prices = array_column($catalogue['products'], 'price', 'id');
            $format = fn($price) => number_format($price, 2, $page['lang'] === 'en' ? '.' : ',', '') . ' KM';
            $content = preg_replace_callback('~(<article[^>]*data-product="([^"]+)"[\s\S]*?<div class="product-card-top">[\s\S]*?<span>)[^<]*(</span>)~', fn($m) => $m[1] . (isset($prices[$m[2]]) ? esc_html($format($prices[$m[2]])) : '') . $m[3], $content);
            if (str_starts_with($page['route'], '/product/')) {
                $key = substr($page['route'], 9);
                if (isset($prices[$key])) { $content = preg_replace_callback('~(<p class="product-price">)[^<]*~', fn($m) => $m[1] . esc_html($format($prices[$key])), $content); }
            }
            echo slovenka_html($content); // Trusted imported HTML; normal WP editor permissions apply.
        }
    }
}
