<?php
if (!defined('ABSPATH')) { exit; }
function slovenka_page() {
    $id = function_exists('is_shop') && is_shop() ? wc_get_page_id('shop') : get_queried_object_id();
    $file = get_post_meta($id, '_slovenka_source', true);
    return slovenka_data()['pages'][$file] ?? null;
}
function slovenka_urls() {
    $urls = [];
    foreach (get_option('slovenka_pages', []) as $file => $id) {
        if (str_starts_with($file, 'product-')) {
            $product = slovenka_legacy_product($file);
            if ($product) { $urls[$file] = $product->get_permalink(); }
        } else { $urls[$file] = get_permalink($id); }
    }
    foreach (slovenka_catalogue()['products'] as $item) {
        foreach (['', '-en'] as $suffix) { $urls['product-' . $item['id'] . $suffix . '.html'] = $item['permalink']; }
    }
    if (function_exists('wc_get_cart_url')) {
        // With plain permalinks the shop page_id redirects to the product
        // archive and drops filters. Link directly to the canonical archive.
        $urls['shop.html'] = get_post_type_archive_link('product');
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
    $en = (slovenka_page()['lang'] ?? 'bs') === 'en';
    $html = preg_replace_callback('~<form\b([^>]*(?:class="project-form"|data-contact-form)[^>]*)>~', function ($m) use ($en) {
        $attrs = preg_replace('~\s(?:action|method|enctype)=(["\']).*?\1~', '', $m[1]);
        return '<form' . $attrs . ' method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-ajax.php')) . '">'
            . '<input type="hidden" name="action" value="mk_inquiry"><input type="hidden" name="nonce" value="' . esc_attr(wp_create_nonce('mk-commerce')) . '">'
            . '<input type="hidden" name="lang" value="' . ($en ? 'en' : 'bs') . '">'
            . '<input type="hidden" name="kind" value="' . (str_contains($m[1], 'data-contact-form') ? 'contact' : 'inquiry') . '">';
    }, $html);
    $html = preg_replace('~(<p class="form-aside-note">)[\s\S]*?(</p>)~', '$1' . ($en ? 'Your inquiry is sent to the MK Slovenka team.' : 'Upit se šalje timu MK Slovenka.') . '$2', $html);
    $html = preg_replace_callback('~(<p[^>]*id="contact-form-help"[^>]*>)([\s\S]*?)(</p>)~', function ($m) use ($en) {
        preg_match('~<a\b[^>]*>[\s\S]*?</a>~', $m[2], $link);
        return $m[1] . ($en ? 'Your message is sent to the MK Slovenka team. ' : 'Poruka se šalje timu MK Slovenka. ') . ($link[0] ?? '') . $m[3];
    }, $html);
    $html = str_replace(['Pripremite email', 'Prepare email'], ['Pošaljite poruku', 'Send message'], $html);
    $html = preg_replace('/(?:Probne narudžbe,\s*)?proizvodni upiti čuvaju se samo u ovom pregledniku\./u', 'Proizvodni i kontakt upiti čuvaju se na serveru i dostupni su ovlaštenom osoblju.', $html);
    $html = preg_replace('/(?:Preview\s*)?orders and production inquiries are stored only in\s*this browser\./', 'Orders are handled by WooCommerce; production and contact inquiries are saved on the server for authorized staff.', $html);
    $html = preg_replace('/Ovaj pregled ne šalje poruke\s*e-mailom, ne obrađuje plaćanja i ne koristi analitičko praćenje\./u', 'Upiti se prosljeđuju e-mailom ako je mail server dostupan. Kupovinu obrađuje WooCommerce.', $html);
    $html = preg_replace('/This preview\s*does not email messages, process payments or use analytics tracking\./', 'Inquiries are forwarded by email when mail delivery is available. Purchases are handled by WooCommerce.', $html);
    $html = str_replace(['Kontakt obrazac priprema poruku u vašoj aplikaciji za email; poruku šaljete iz te aplikacije.', 'The contact form prepares a message in your email app; you send it from that app.'], ['Kontakt obrazac dostavlja poruku timu MK Slovenka.', 'The contact form sends your message to the MK Slovenka team.'], $html);
    // Replace only known local documents; preserve queries, anchors and external URLs.
    $html = preg_replace_callback('~\b(href|action)=(["\'])([^"\']+)\2~', function ($match) use ($urls) {
        $parts = preg_split('/(?=[?#])/', $match[3], 2);
        if (!isset($urls[$parts[0]])) { return $match[0]; }
        $url = $urls[$parts[0]];
        $suffix = $parts[1] ?? '';
        if (str_starts_with($suffix, '?') && str_contains($url, '?')) { $suffix = '&' . substr($suffix, 1); }
        return $match[1] . '=' . $match[2] . esc_url($url . $suffix) . $match[2];
    }, $html);
    $html = preg_replace_callback('~(?<=["\'\s(,])(assets/[^"\'\s),]+|styles\.css(?:\?[^"\'\s]+)?|(?:script|motion|contact-map)\.js(?:\?[^"\'\s]+)?)~', function ($m) use ($base) {
        $url = $base . $m[1];
        if (preg_match('/\.(?:js|css)(?:\?|$)/', $m[1])) $url = add_query_arg('ver', wp_get_theme()->get('Version'), $url);
        return esc_url($url);
    }, $html);
    return $html;
}
function slovenka_config() {
    $catalogue = slovenka_catalogue();
    return array_merge($catalogue, ['urls' => slovenka_urls(), 'assetBase' => get_template_directory_uri() . '/', 'production' => true, 'ajaxUrl' => admin_url('admin-ajax.php'), 'inquiryNonce' => wp_create_nonce('mk-commerce'), 'cart' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '', 'cartCount' => function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0]);
}
function slovenka_content() {
    if (is_404()) {
        echo '<section class="container section-space"><h1>Stranica nije dostupna / Page unavailable</h1><a href="' . esc_url(home_url('/')) . '">Početna / Home</a></section>';
        return;
    }
    if (function_exists('is_product') && is_product()) {
        echo '<section class="container section-space native-product">';
        woocommerce_content();
        echo '</section>';
        return;
    }
    $page = slovenka_page();
    if (!$page) {
        if (function_exists('is_woocommerce') && is_woocommerce()) { woocommerce_content(); return; }
        while (have_posts()) { the_post(); the_title('<h1>', '</h1>'); the_content(); }
        return;
    }
    // The shop is a product archive: its main query contains products, not the
    // imported shop page. Render that page once without WooCommerce's sanitized
    // archive-description pipeline (which removes selects and breaks the JS).
    if (function_exists('is_shop') && is_shop()) {
        slovenka_render_page_content(get_post_field('post_content', wc_get_page_id('shop')), $page);
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
            slovenka_render_page_content(get_the_content(), $page);
        }
    }
}

function slovenka_render_page_content($content, $page) {
    $content = preg_replace('~<p class="(?:catalog-note|preview-helper)">[\s\S]*?</p>~', '', $content);
    // Replace saved grids as well as new placeholders, without overwriting page edits.
    $content = slovenka_live_product_grids($content, $page['route'] === '/shop' ? 0 : 4);
    echo slovenka_html($content);
}

function slovenka_live_product_grids($html, $limit) {
    $offset = 0;
    while (preg_match('~<div\b[^>]*class=["\'][^"\']*\bproduct-grid\b[^"\']*["\'][^>]*>~', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $match[0][1]; $end = $start + strlen($match[0][0]); $depth = 1;
        while ($depth && preg_match('~</?div\b[^>]*>~', $html, $tag, PREG_OFFSET_CAPTURE, $end)) {
            $depth += str_starts_with($tag[0][0], '</') ? -1 : 1;
            $end = $tag[0][1] + strlen($tag[0][0]);
        }
        if ($depth) { break; }
        $replacement = slovenka_product_grid($limit);
        $html = substr_replace($html, $replacement, $start, $end - $start);
        $offset = $start + strlen($replacement);
    }
    return $html;
}

function slovenka_product_grid($limit) {
    $items = slovenka_catalogue()['products'];
    if ($limit) { $items = array_slice($items, 0, $limit); }
    $html = '<div class="product-grid' . ($limit ? '' : ' listing-grid') . '" data-live-products="' . (int)$limit . '">';
    foreach ($items as $item) {
        $product = wc_get_product((int)$item['id']);
        $url = esc_url($product->get_permalink());
        $html .= '<article class="product-card" data-product="' . esc_attr($item['id']) . '"><a class="product-image" href="' . $url . '">' . $product->get_image('woocommerce_single') . '</a><div class="product-card-top"><a href="' . $url . '"><h3>' . esc_html($item['name']) . '</h3></a><span>' . $item['priceHtml'] . '</span></div><a class="product-description" href="' . $url . '">' . esc_html($item['label']['bs']) . '</a></article>';
    }
    return $html . '</div>';
}
