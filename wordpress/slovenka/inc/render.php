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
            $item['sizeTables'] = mk_product_size_tables($id);
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
    return array_merge($catalogue, ['urls' => slovenka_urls(), 'assetBase' => get_template_directory_uri() . '/', 'production' => true, 'ajaxUrl' => admin_url('admin-ajax.php'), 'inquiryNonce' => wp_create_nonce('mk-commerce'), 'cart' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '', 'cartCount' => function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0]);
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
                $tables = array_values(array_filter($catalogue['products'], fn($item) => $item['id'] === $key));
                $has_tables = !empty($tables[0]['sizeTables']);
                $note = $has_tables
                    ? ($page['lang'] === 'en' ? 'See the size guide for this model’s garment measurements.' : 'Mjere ovog modela pogledajte u tabeli mjera.')
                    : ($page['lang'] === 'en' ? 'Contact us for help choosing your size.' : 'Kontaktirajte nas za pomoć pri odabiru veličine.');
                $content = preg_replace('/(?:Veličine u pregledu su primjeri\.\s*Tačne mjere modela još nisu\s*objavljene\.|Preview sizes are examples\.\s*Exact garment measurements have\s*not been published yet\.)/u', $note, $content);
                if (isset($prices[$key])) { $content = preg_replace_callback('~(<p class="product-price">)[^<]*~', fn($m) => $m[1] . esc_html($format($prices[$key])), $content); }
            }
            echo slovenka_html($content); // Trusted imported HTML; normal WP editor permissions apply.
        }
    }
}
