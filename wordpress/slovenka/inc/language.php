<?php
if (!defined('ABSPATH')) { exit; }

function slovenka_default_language() {
    $front = (int)get_option('page_on_front');
    $source = $front ? get_post_meta($front, '_slovenka_source', true) : '';
    return slovenka_data()['pages'][$source]['lang'] ?? (str_starts_with((string)get_option('WPLANG'), 'bs') ? 'bs' : 'en');
}
function slovenka_english_path($url = null) {
    $path = wp_parse_url($url ?? ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $base = trailingslashit(wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/');
    return str_starts_with($path, $base . 'en/') || $path === $base . 'en';
}
function slovenka_requested_language() {
    $lang = isset($_GET['lang']) && is_string($_GET['lang']) ? wp_unslash($_GET['lang']) : '';
    if (in_array($lang, ['bs', 'en'], true)) { return $lang; }
    if (slovenka_english_path()) { return 'en'; }
    // Store API validation messages follow the language of the calling checkout.
    $header = $_SERVER['HTTP_X_SLOVENKA_LANGUAGE'] ?? '';
    if (slovenka_store_api_request() && in_array($header, ['bs','en'], true)) { return $header; }
    return slovenka_default_language();
}
function slovenka_store_api_request() {
    $route = $_GET['rest_route'] ?? ($_SERVER['REQUEST_URI'] ?? '');
    return is_string($route) && str_contains($route, '/wc/store/');
}
function slovenka_frontend_locale($locale) {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST && !slovenka_store_api_request()) || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) { return $locale; }
    $lang = did_action('wp') ? slovenka_language() : slovenka_requested_language();
    return $lang === 'bs' ? 'bs_BA' : 'en_US';
}
add_filter('locale', 'slovenka_frontend_locale', 20);
add_filter('determine_locale', 'slovenka_frontend_locale', 20);
add_filter('load_textdomain_mofile', function ($file, $domain) {
    if ($domain === 'woocommerce' && !is_admin() && !(defined('WP_CLI') && WP_CLI) && !wp_doing_cron()
        && (!(defined('REST_REQUEST') && REST_REQUEST) || slovenka_store_api_request())
        && slovenka_requested_language() === 'bs' && !is_readable($file)) {
        return get_theme_file_path('languages/woocommerce-bs_BA.mo');
    }
    return $file;
}, 20, 2);
add_action('wp', function () {
    if (is_admin() || !class_exists('WooCommerce')) { return; }
    unload_textdomain('woocommerce', true);
    if (slovenka_language() !== 'bs') { return; }
    $installed = WP_LANG_DIR . '/plugins/woocommerce-bs_BA.mo';
    load_textdomain('woocommerce', is_readable($installed) ? $installed : get_theme_file_path('languages/woocommerce-bs_BA.mo'), 'bs_BA');
}, 1);

// Duplicate native rewrite rules without adding capture groups, preserving
// WooCommerce endpoints, pagination, taxonomy routes and subdirectory installs.
add_filter('query_vars', function ($vars) { $vars[] = 'slovenka_lang'; return $vars; });
add_filter('request', function ($query) {
    // Plain-permalink stores do not generate rewrite rules, but /en/?s=...
    // and /en/?product=... still carry their language in the request path.
    if (slovenka_english_path() && !$query) {
        $path = untrailingslashit(wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $root = untrailingslashit(wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '');
        $query = $path === $root . '/en' ? ['slovenka_lang'=>'en'] : ['error'=>'404'];
    }
    if (($query['slovenka_lang'] ?? '') === 'en' && count($query) === 1) {
        $id = (int)(get_option('slovenka_pages', [])['index-en.html'] ?? 0);
        if ($id && get_post_status($id) === 'publish') { $query['page_id'] = $id; }
    }
    return $query;
});
add_filter('rewrite_rules_array', function ($rules) {
    $english = ['en/?$'=>'index.php?slovenka_lang=en'];
    foreach ($rules as $pattern=>$query) {
        if (str_starts_with($pattern, 'en/')) { continue; }
        $english['en/' . ltrim($pattern, '^')] = $query . (str_contains($query, '?') ? '&' : '?') . 'slovenka_lang=en';
    }
    return $english + $rules;
});
add_action('init', function () {
    $version = '1.3.0';
    if (get_option('slovenka_language_routes_version') !== $version) {
        // One soft flush on upgrade, after post types and Woo endpoints register.
        add_action('wp_loaded', function () use ($version) {
            flush_rewrite_rules(false);
            update_option('slovenka_language_routes_version', $version, false);
        }, 99);
    }
}, 99);
add_action('after_switch_theme', function () { delete_option('slovenka_language_routes_version'); });

/** Public language URLs are path based; service/admin/asset URLs stay native. */
function slovenka_language_url($url, $lang = null) {
    if (!is_string($url) || !$url || is_admin()) { return $url; }
    $parts = wp_parse_url(html_entity_decode($url));
    if ($parts === false || (isset($parts['scheme']) && !in_array($parts['scheme'], ['http','https'], true))) { return $url; }
    if (isset($parts['host']) && (strtolower($parts['host']) !== strtolower(wp_parse_url(home_url('/'), PHP_URL_HOST)) || ($parts['port'] ?? null) !== wp_parse_url(home_url('/'), PHP_URL_PORT))) { return $url; }
    if (str_starts_with($url, '#')) { return $url; }
    $base = trailingslashit(wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/');
    $path = $parts['path'] ?? $base;
    if (!str_starts_with($path, $base)) { return $url; }
    $relative = substr($path, strlen($base));
    $relative = preg_replace('~^en(?:/|$)~', '', $relative);
    if (preg_match('~^(?:wp-admin|wp-json|wp-content|wp-includes)(?:/|$)|^wp-login\.php$~', $relative)) { return $url; }
    parse_str($parts['query'] ?? '', $query);
    if (isset($query['rest_route'])) { return remove_query_arg('lang', $url); }
    $lang = $lang ?? ($query['lang'] ?? slovenka_language());
    $lang = $lang === 'en' ? 'en' : 'bs';
    unset($query['lang'], $query['slovenka_lang']);
    $root = untrailingslashit(home_url('/'));
    // Existing editorial translations already have distinct public permalinks.
    $id = (int)($query['page_id'] ?? 0);
    if (!$id && $relative !== '') { $post = get_page_by_path(trim($relative, '/')); $id = $post ? $post->ID : 0; }
    $source = $id ? get_post_meta($id, '_slovenka_source', true) : '';
    $editorial = $source && !preg_match('~^(?:shop|cart|checkout|order)(?:-en)?\.html$~', $source);
    if ($editorial) {
        $alternate = $lang === 'en' ? preg_replace('~(?:-en)?\.html$~', '-en.html', $source) : str_replace('-en.html', '.html', $source);
        $other_id = (int)(get_option('slovenka_pages', [])[$alternate] ?? 0);
        if ($other_id && get_post_status($other_id) === 'publish') {
            // Read the unfiltered permalink to avoid page_link recursion.
            static $resolving = false;
            if (!$resolving) {
                $resolving = true; $target = get_permalink($other_id); $resolving = false;
                $target_parts = wp_parse_url($target);
                $relative = substr($target_parts['path'] ?? $base, strlen($base));
                unset($query['page_id']);
                parse_str($target_parts['query'] ?? '', $target_query);
                $query = $target_query + $query;
            }
        }
    }
    $target = $root . '/' . (!$editorial && $lang === 'en' ? 'en/' : '') . $relative;
    if ($query) { $target .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986); }
    return $target . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}
function slovenka_current_url() {
    $uri = wp_unslash($_SERVER['REQUEST_URI'] ?? '/');
    $origin = wp_parse_url(home_url('/'));
    return $origin['scheme'] . '://' . $origin['host'] . (isset($origin['port']) ? ':' . $origin['port'] : '') . '/' . ltrim($uri, '/');
}
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET','HEAD'], true) || is_preview()) { return; }
    if (isset($_GET['lang']) && is_string($_GET['lang']) && in_array($_GET['lang'], ['bs','en'], true)) {
        $action = isset($_GET['wc-ajax']) || isset($_GET['add-to-cart']) || isset($_GET['remove_item']) || isset($_GET['_wpnonce']);
        wp_safe_redirect(slovenka_language_url(slovenka_current_url(), $_GET['lang']), $action ? 302 : 301); exit;
    }
}, 1);
add_filter('redirect_canonical', function ($redirect) {
    return $redirect && slovenka_english_path() ? slovenka_language_url($redirect, 'en') : $redirect;
});
foreach (['woocommerce_get_shop_page_permalink', 'woocommerce_get_cart_url', 'woocommerce_get_checkout_url', 'woocommerce_get_myaccount_page_permalink', 'woocommerce_get_checkout_order_received_url', 'get_pagenum_link'] as $hook) {
    add_filter($hook, 'slovenka_language_url');
}
add_filter('woocommerce_ajax_get_endpoint', function ($url) {
    return str_replace('%25%25endpoint%25%25', '%%endpoint%%', slovenka_language_url($url));
});
// WooCommerce redirects plain-permalink shop page links to this archive URL.
add_filter('post_type_archive_link', function ($url, $type) {
    return $type === 'product' ? slovenka_language_url($url) : $url;
}, 10, 2);
add_filter('post_type_link', function ($url, $post) {
    return $post->post_type === 'product' ? slovenka_language_url($url) : $url;
}, 10, 2);
add_filter('term_link', function ($url, $term) {
    return str_starts_with($term->taxonomy, 'product_') ? slovenka_language_url($url) : $url;
}, 10, 2);
add_filter('page_link', function ($url, $id) {
    if (!function_exists('wc_get_page_id')) { return $url; }
    foreach (['shop', 'cart', 'checkout', 'myaccount'] as $role) {
        if ((int)$id === wc_get_page_id($role)) { return slovenka_language_url($url); }
    }
    return $url;
}, 10, 2);
// Imported editorial content contains saved links, so localize those as well.
add_filter('the_content', function ($content) {
    if (is_admin()) { return $content; }
    $html = new WP_HTML_Tag_Processor($content);
    while ($html->next_tag('a')) {
        $url = $html->get_attribute('href');
        if (is_string($url)) { $html->set_attribute('href', slovenka_language_url($url)); }
    }
    return $html->get_updated_html();
}, 20);
add_filter('nav_menu_link_attributes', function ($attributes) {
    if (isset($attributes['href'])) { $attributes['href'] = slovenka_language_url($attributes['href']); }
    return $attributes;
});
// Translate technical attribute keys used by the starter import, keeping merchant
// labels and product descriptions under WooCommerce's normal content workflow.
add_filter('woocommerce_attribute_label', function ($label) {
    if (is_admin() || slovenka_language() !== 'bs') { return $label; }
    return ['colour'=>'Boja', 'color'=>'Boja', 'size'=>'Veličina'][$label] ?? $label;
});
add_filter('woocommerce_variation_option_name', function ($name, $term, $attribute) {
    if (is_admin() || slovenka_language() !== 'bs' || !in_array($attribute, ['colour', 'color', 'pa_colour', 'pa_color'], true)) { return $name; }
    return ['black'=>'Crna', 'brown'=>'Smeđa', 'white'=>'Bijela', 'grey'=>'Siva', 'gray'=>'Siva', 'charcoal'=>'Antracit', 'burgundy'=>'Bordo', 'bronze'=>'Bronzana', 'beige'=>'Bež', 'navy'=>'Tamnoplava', 'blue'=>'Plava', 'green'=>'Zelena'][strtolower($name)] ?? $name;
}, 10, 3);
add_filter('woocommerce_breadcrumb_home_url', fn() => slovenka_home_url());
add_filter('woocommerce_get_endpoint_url', 'slovenka_language_url');
add_filter('get_custom_logo', function ($logo) {
    $html = new WP_HTML_Tag_Processor($logo);
    if ($html->next_tag('a')) { $html->set_attribute('href', slovenka_home_url()); }
    return $html->get_updated_html();
});
