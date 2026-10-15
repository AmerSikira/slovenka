<?php
if (!defined('ABSPATH')) { exit; }
function slovenka_page() {
    // A taxonomy's queried object ID belongs to the terms table, never postmeta.
    if (!is_singular('page')) { return null; }
    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) { return null; }
    $file = get_post_meta(get_queried_object_id(), '_slovenka_source', true);
    return slovenka_data()['pages'][$file] ?? null;
}
function slovenka_language() {
    return (did_action('wp') ? (slovenka_page()['lang'] ?? null) : null) ?? slovenka_requested_language();
}
function slovenka_text($bosnian, $english) {
    return slovenka_language() === 'bs' ? $bosnian : $english;
}
function slovenka_page_url($file) {
    $id = (int)(get_option('slovenka_pages', [])[$file] ?? 0);
    return $id && get_post_status($id) === 'publish' ? get_permalink($id) : '';
}
function slovenka_home_url() {
    return slovenka_page_url('index' . (slovenka_language() === 'en' ? '-en' : '') . '.html') ?: slovenka_language_url(home_url('/'));
}
function slovenka_shop_menu_toggle() {
    return '<button class="shop-menu-toggle" type="button" aria-expanded="false" aria-label="' . esc_attr(slovenka_text('Prikaži kategorije proizvoda', 'Show product categories')) . '"><span aria-hidden="true">⌄</span></button>';
}
function slovenka_menu_categories() {
    if (!taxonomy_exists('product_cat')) { return []; }
    $terms = get_terms(['taxonomy'=>'product_cat', 'hide_empty'=>true]);
    return is_wp_error($terms) ? [] : $terms;
}
function slovenka_primary_fallback() {
    echo '<ul class="primary-menu">';
    echo '<li><a href="' . esc_url(slovenka_home_url()) . '">' . esc_html(slovenka_text('Početna','Home')) . '</a></li>';
    if (function_exists('wc_get_page_permalink')) {
        $terms = slovenka_menu_categories();
        echo '<li class="' . ($terms ? 'shop-menu-item menu-item-has-children' : '') . '"><a href="' . esc_url(wc_get_page_permalink('shop')) . '">' . esc_html(slovenka_text('Trgovina','Shop')) . '</a>';
        if ($terms) {
            echo slovenka_shop_menu_toggle() . '<ul class="sub-menu">';
            slovenka_category_links($terms, true);
            echo '</ul>';
        }
        echo '</li>';
    }
    foreach (['company'=>['O nama','Our company'], 'manufacturing'=>['Proizvodnja','Manufacturing'], 'contact'=>['Kontakt','Contact']] as $name=>$label) {
        $url = slovenka_page_url($name . (slovenka_language() === 'en' ? '-en' : '') . '.html');
        if ($url) { echo '<li><a href="' . esc_url($url) . '">' . esc_html(slovenka_text(...$label)) . '</a></li>'; }
    }
    echo '</ul>';
}
function slovenka_category_links($terms = null, $list = false) {
    foreach ($terms ?? slovenka_menu_categories() as $term) {
        $url = get_term_link($term);
        if (!is_wp_error($url)) { echo ($list ? '<li>' : '') . '<a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a>' . ($list ? '</li>' : ''); }
    }
}
// Starter items follow the current language without rewriting saved merchant choices.
add_filter('wp_nav_menu_objects', function ($items, $args) {
    if (($args->theme_location ?? '') !== 'primary') { return $items; }
    $seed = get_option('slovenka_primary_menu_seed', []);
    $labels = ['index.html'=>['Početna','Home'], 'shop.html'=>['Trgovina','Shop'], 'company.html'=>['O nama','Our company'], 'manufacturing.html'=>['Proizvodnja','Manufacturing'], 'contact.html'=>['Kontakt','Contact']];
    foreach ($items as $item) {
        $file = get_post_meta($item->ID, '_slovenka_navigation_key', true);
        $original = $seed[$item->ID]['signature'] ?? null;
        if (!isset($labels[$file]) || !$original) { continue; }
        if (in_array($item->title, $labels[$file], true)) { $item->title = slovenka_text(...$labels[$file]); }
        foreach (['type','object','object_id','url'] as $field) {
            $current = $item->$field;
            $saved = $original[$field] ?? null;
            if ($field === 'url') {
                $current = untrailingslashit(remove_query_arg('lang', html_entity_decode($current)));
                $saved = untrailingslashit(remove_query_arg('lang', html_entity_decode($saved ?? '')));
            }
            if ((string)$current !== (string)$saved) { continue 2; }
        }
        if ($file === 'index.html') { $item->url = slovenka_home_url(); }
        elseif ($file === 'shop.html' && function_exists('wc_get_page_permalink')) { $item->url = wc_get_page_permalink('shop'); }
        else {
            $url = slovenka_page_url(slovenka_language() === 'en' ? str_replace('.html', '-en.html', $file) : $file);
            if ($url) { $item->url = $url; }
        }
    }
    return $items;
}, 10, 2);
// Decorate the displayed menu only; merchant menu records and child links stay intact.
add_filter('wp_nav_menu_objects', function ($items, $args) {
    if (($args->theme_location ?? '') !== 'primary') { return $items; }
    $shop = null;
    $shop_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
    $normalize = static function ($url) { return untrailingslashit(remove_query_arg('lang', html_entity_decode($url))); };
    foreach ($items as $item) {
        if ($item->menu_item_parent) { continue; }
        if (($item->object === 'page' && $shop_id > 0 && (int)$item->object_id === $shop_id)
            || ($item->type === 'post_type_archive' && $item->object === 'product')
            || ($shop_url && $normalize($item->url) === $normalize($shop_url))) { $shop = $item; break; }
    }
    $legacy_ids = [];
    foreach ($items as $item) {
        if (!$item->menu_item_parent && in_array(strtolower(trim(wp_strip_all_tags($item->title))), ['kategorije', 'categories'], true)) { $legacy_ids[] = $item->ID; }
    }
    $items = array_values(array_filter($items, static fn($item) => !in_array($item->ID, $legacy_ids, true)));
    foreach ($items as $item) {
        if (in_array((int)$item->menu_item_parent, array_map('intval', $legacy_ids), true)) { $item->menu_item_parent = $shop ? $shop->ID : 0; }
    }
    if (!$shop) { return $items; }
    $children = [];
    $parents = [(int)$shop->ID];
    do {
        $added = false;
        foreach ($items as $item) {
            if (in_array((int)$item->menu_item_parent, $parents, true) && !in_array((int)$item->ID, $parents, true)) {
                $parents[] = (int)$item->ID; $children[] = $item; $added = true;
            }
        }
    } while ($added);
    foreach (slovenka_menu_categories() as $term) {
        $url = get_term_link($term);
        if (is_wp_error($url)) { continue; }
        foreach ($children as $child) {
            if (($child->object === 'product_cat' && (int)$child->object_id === (int)$term->term_id) || $normalize($child->url) === $normalize($url)) { continue 2; }
        }
        $items[] = (object)[
            'ID'=>-$term->term_id, 'db_id'=>-$term->term_id, 'menu_item_parent'=>$shop->ID,
            'object_id'=>$term->term_id, 'object'=>'product_cat', 'type'=>'taxonomy',
            'title'=>$term->name, 'url'=>$url, 'classes'=>['menu-item', 'menu-item-type-taxonomy'],
            'target'=>'', 'attr_title'=>'', 'description'=>'', 'xfn'=>'', 'current'=>false,
        ];
    }
    foreach ($items as $item) {
        if ((int)$item->menu_item_parent === (int)$shop->ID) {
            $shop->classes[] = 'shop-menu-item';
            $shop->classes[] = 'menu-item-has-children';
            $shop->classes = array_unique($shop->classes);
            break;
        }
    }
    return $items;
}, 20, 2);
add_filter('walker_nav_menu_start_el', function ($output, $item, $depth, $args) {
    if (($args->theme_location ?? '') === 'primary' && in_array('shop-menu-item', $item->classes, true)) { $output .= slovenka_shop_menu_toggle(); }
    return $output;
}, 20, 4);
add_shortcode('slovenka_form', function ($attributes, $content = '') {
    $attributes = shortcode_atts(['kind'=>'inquiry','lang'=>slovenka_language()], $attributes, 'slovenka_form');
    $kind = $attributes['kind'] === 'contact' ? 'contact' : 'inquiry';
    $lang = $attributes['lang'] === 'en' ? 'en' : 'bs';
    $processor = new WP_HTML_Tag_Processor($content);
    if (!$processor->next_tag('form')) { return ''; }
    $processor->set_attribute('method', 'post');
    $processor->set_attribute('action', admin_url('admin-ajax.php'));
    $processor->set_attribute('enctype', 'multipart/form-data');
    $content = $processor->get_updated_html();
    $hidden = '<input type="hidden" name="action" value="mk_inquiry">' . wp_nonce_field('mk-commerce', 'nonce', false, false)
        . '<input type="hidden" name="kind" value="' . esc_attr($kind) . '"><input type="hidden" name="lang" value="' . esc_attr($lang) . '">';
    return preg_replace_callback('~<form\b[^>]*>~i', fn($match) => $match[0] . $hidden, $content, 1);
});
