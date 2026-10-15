<?php
/** Optional starter content setup. No activation, cron or admin-init imports. */
if (!defined('ABSPATH')) { exit; }
function slovenka_data() {
    static $data;
    return $data ??= json_decode(file_get_contents(get_template_directory() . '/data/site.json'), true, 512, JSON_THROW_ON_ERROR);
}
function slovenka_find($type, $key, $value) {
    $ids = get_posts(['post_type'=>$type,'post_status'=>'any','meta_key'=>$key,'meta_value'=>$value,'fields'=>'ids','posts_per_page'=>1]);
    return $ids ? (int)$ids[0] : 0;
}
function slovenka_import_url($value, $pages) {
    if (str_starts_with($value, 'assets/')) { return get_theme_file_uri($value); }
    $parts = wp_parse_url(html_entity_decode($value, ENT_QUOTES));
    if (!$parts || isset($parts['host']) || isset($parts['scheme'])) { return $value; }
    $file = $parts['path'] ?? '';
    $url = isset($pages[$file]) ? get_permalink($pages[$file]) : '';
    if (function_exists('wc_get_page_permalink')) {
        foreach (['shop','cart','checkout'] as $role) {
            if (in_array($file, [$role . '.html',$role . '-en.html'], true)) {
                $url = $role === 'shop' ? get_post_type_archive_link('product') : (wc_get_page_id($role) > 0 ? wc_get_page_permalink($role) : (isset($pages[$role . '.html']) ? get_permalink($pages[$role . '.html']) : home_url('/')));
            }
        }
        if (str_starts_with($file, 'product-')) { $product = slovenka_legacy_product($file); $url = $product ? $product->get_permalink() : get_post_type_archive_link('product'); }
    }
    if (!$url) { return $value; }
    parse_str($parts['query'] ?? '', $query);
    if (isset($query['category']) && taxonomy_exists('product_cat')) {
        $term = get_term_by('slug', $query['category'], 'product_cat');
        $term_url = $term ? get_term_link($term) : add_query_arg('product_cat', sanitize_title($query['category']), home_url('/'));
        if (!is_wp_error($term_url)) { $url = $term_url; }
        unset($query['category']);
    }
    if (isset($query['q'])) { $query['s'] = $query['q']; $query['post_type'] = 'product'; unset($query['q']); }
    unset($query['edit']); // The preview's fixed "new collection" list has no production query equivalent.
    if ($query) { $url = add_query_arg($query, $url); }
    return $url . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}
function slovenka_import_product_blocks($html) {
    $offset = 0;
    while (preg_match('~<div\b[^>]*class=["\'][^"\']*\bproduct-grid\b[^"\']*["\'][^>]*>~', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $match[0][1]; $end = $start + strlen($match[0][0]); $depth = 1;
        while ($depth && preg_match('~</?div\b[^>]*>~', $html, $tag, PREG_OFFSET_CAPTURE, $end)) {
            $depth += str_starts_with($tag[0][0], '</') ? -1 : 1;
            $end = $tag[0][1] + strlen($tag[0][0]);
        }
        if ($depth) { break; }
        $replacement = '[products limit="4" columns="4" orderby="date" order="DESC"]';
        $html = substr_replace($html, $replacement, $start, $end - $start);
        $offset = $start + strlen($replacement);
    }
    return $html;
}
function slovenka_prepare_content($content, $page, $pages) {
    $route = $page['route'];
    // Remove only identified preview catalogue elements; merchant additions survive.
    if ($route === '/shop' && preg_match('~class=["\'][^"\']*\b(?:listing-grid|listing-toolbar)\b~', $content)) {
        foreach (['category-tabs','listing-toolbar','listing-grid','breadcrumbs'] as $class) {
            while (preg_match('~<([a-z][a-z0-9]*)\b[^>]*class=["\'][^"\']*\b' . $class . '\b[^"\']*["\'][^>]*>~i', $content, $match, PREG_OFFSET_CAPTURE)) {
                $tag = $match[1][0]; $start = $match[0][1]; $end = $start + strlen($match[0][0]); $depth = 1;
                while ($depth && preg_match('~</?' . $tag . '\b[^>]*>~i', $content, $token, PREG_OFFSET_CAPTURE, $end)) {
                    $depth += str_starts_with($token[0][0], '</') ? -1 : 1;
                    $end = $token[0][1] + strlen($token[0][0]);
                }
                if ($depth) { break; }
                $content = substr_replace($content, '', $start, $end - $start);
            }
        }
        $content = preg_replace('~<h1\b[^>]*>[\s\S]*?</h1>~i', '', $content, 1);
        $content = preg_replace('~<p\b[^>]*class="eyebrow"[^>]*>[\s\S]*?</p>~i', '', $content, 1);
    }
    $content = strtr($content, slovenka_data()['image_aliases'] ?? []);
    $content = slovenka_import_product_blocks($content);
    // These exact previous default strings migrate the requested copy without changing edited text.
    $content = str_replace(['Odjeća za vaš stil.<br/>Proizvodnja za vaš brend.','Odaberite svoj put do odjeće.','Vaš potpis.<br/>Naše iskustvo u izradi.'], ['Mjesto gdje moda oživljava.','Vaš put u svijet mode.','Ideje koje postaju stvarnost.'], $content);
    if (!str_contains($content, '[slovenka_form')) {
        $content = preg_replace_callback('~<form\b[^>]*(?:class="project-form"|data-contact-form)[^>]*>[\s\S]*?</form>~i', function ($match) use ($page) {
            $kind = str_contains($match[0], 'data-contact-form') ? 'contact' : 'inquiry';
            return '[slovenka_form kind="' . $kind . '" lang="' . esc_attr($page['lang']) . '"]' . $match[0] . '[/slovenka_form]';
        }, $content);
    }
    $processor = new WP_HTML_Tag_Processor($content);
    while ($processor->next_tag()) {
        foreach (['href','src','action','srcset'] as $attribute) {
            $value = $processor->get_attribute($attribute);
            if (!is_string($value)) { continue; }
            if ($attribute === 'srcset') {
                $value = preg_replace_callback('~(?:^|,\s*)(assets/[^\s,]+)~', fn($match) => str_replace($match[1], get_theme_file_uri($match[1]), $match[0]), $value);
            } else { $value = slovenka_import_url($value, $pages); }
            $processor->set_attribute($attribute, $value);
        }
    }
    $content = $processor->get_updated_html();
    if (!has_blocks($content)) {
        $content = in_array($route, ['/cart','/checkout'], true)
            ? '<!-- wp:shortcode -->' . $content . '<!-- /wp:shortcode -->'
            : '<!-- wp:html -->' . $content . '<!-- /wp:html -->';
    }
    return $content;
}
function slovenka_import_site($assign_empty = false) {
    if (!current_user_can('manage_options')) { return new WP_Error('forbidden', __('Not permitted.','slovenka')); }
    $data = slovenka_data(); $pages = get_option('slovenka_pages', []); $created = []; $updated = 0;
    foreach ($data['pages'] as $file=>$page) {
        $id = slovenka_find('page', '_slovenka_source', $file);
        // Existing merchant-owned WooCommerce pages remain exactly as configured.
        $role = ['shop.html'=>'shop','cart.html'=>'cart','checkout.html'=>'checkout'][$file] ?? '';
        $configured = $role && function_exists('wc_get_page_id') ? wc_get_page_id($role) : 0;
        if ($configured > 0 && get_post_status($configured) === 'publish') { $id = $configured; }
        if (!$id) {
            $id = wp_insert_post(wp_slash(['post_type'=>'page','post_status'=>'draft','post_title'=>$page['title'],'post_name'=>pathinfo($file, PATHINFO_FILENAME),'meta_input'=>['_slovenka_source'=>$file,'_slovenka_seed_pending'=>1]]), true);
            if (is_wp_error($id)) { return $id; }
        }
        $pages[$file] = (int)$id;
    }
    update_option('slovenka_pages', $pages, false);
    foreach ($data['pages'] as $file=>$page) {
        $id = $pages[$file];
        $source = get_post_meta($id, '_slovenka_source', true);
        if ($source !== $file) {
            if (!in_array($file, ['shop.html','cart.html','checkout.html'], true) || $source !== str_replace('.html', '-en.html', $file)) { continue; }
            $page['lang'] = 'en';
        }
        $pending = (bool)get_post_meta($id, '_slovenka_seed_pending', true);
        if (!$pending && get_post_meta($id, '_slovenka_native_content_version', true) === '3') { continue; }
        $old = get_post_field('post_content', $id);
        $content = slovenka_prepare_content($pending ? $page['content'] : $old, $page, $pages);
        if (!$pending) {
            if (!metadata_exists('post', $id, '_slovenka_before_native_upgrade')) { update_post_meta($id, '_slovenka_before_native_upgrade', $old); }
            wp_save_post_revision($id);
        }
        $result = wp_update_post(wp_slash(['ID'=>$id, 'post_content'=>$content] + ($pending ? ['post_status'=>'publish'] : [])), true);
        if (is_wp_error($result)) { return $result; }
        update_post_meta($id, '_slovenka_native_content_version', '3');
        delete_post_meta($id, '_slovenka_seed_pending');
        $pending ? $created[] = $id : $updated++;
    }
    if ($assign_empty) {
        if (!get_post_status((int)get_option('page_on_front'))) { update_option('page_on_front', $pages['index.html']); update_option('show_on_front', 'page'); }
        if (!get_post_status((int)get_option('wp_page_for_privacy_policy'))) { update_option('wp_page_for_privacy_policy', $pages['support-privacy.html']); }
        if (class_exists('WooCommerce')) {
            foreach (['shop'=>'shop.html','cart'=>'cart.html','checkout'=>'checkout.html','terms'=>'support-terms.html'] as $role=>$file) {
                if (!get_post_status((int)get_option('woocommerce_' . $role . '_page_id'))) { update_option('woocommerce_' . $role . '_page_id', $pages[$file]); }
            }
        }
    }
    // Obsolete continuation events must never restart an old automatic import.
    wp_clear_scheduled_hook('slovenka_continue_import');
    return ['created'=>count($created), 'upgraded'=>$updated];
}
add_action('admin_menu', function () {
    add_theme_page('Slovenka setup', 'Slovenka setup', 'manage_options', 'slovenka-setup', 'slovenka_setup_screen');
});
function slovenka_setup_screen() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap"><h1>Slovenka setup</h1><p>Optional: create missing editorial pages and upgrade previously imported HTML to native WordPress blocks/shortcodes. Existing edited content is preserved outside obsolete catalogue markup, and a backup of upgraded content is saved.</p><p>Products are imported separately. This action never changes currency, product data or existing configured store pages.</p>';
    if (isset($_GET['slovenka_setup'])) { echo '<p role="status">' . esc_html(sanitize_text_field(wp_unslash($_GET['slovenka_setup']))) . '</p>'; }
    echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="slovenka_setup">';
    wp_nonce_field('slovenka_setup');
    echo '<p><label><input type="checkbox" name="assign_empty" value="1"> Assign the imported home/privacy/shop/cart/checkout/terms pages only where no valid page is currently configured.</label></p>';
    submit_button('Create missing pages / upgrade imported content');
    echo '</form><p>Then manage content in Pages, navigation in Appearance → Menus, and store assignments/currency in WooCommerce settings. No setup runs automatically.</p></div>';
}
add_action('admin_post_slovenka_setup', function () {
    if (!current_user_can('manage_options')) { wp_die(esc_html__('Not permitted.','slovenka'), '', ['response'=>403]); }
    check_admin_referer('slovenka_setup');
    $result = slovenka_import_site(!empty($_POST['assign_empty']));
    $message = is_wp_error($result) ? $result->get_error_message() : sprintf('Complete: %d pages created, %d imported pages upgraded.', $result['created'], $result['upgraded']);
    wp_safe_redirect(add_query_arg(['page'=>'slovenka-setup','slovenka_setup'=>$message], admin_url('themes.php'))); exit;
});
