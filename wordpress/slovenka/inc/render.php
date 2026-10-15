<?php
if (!defined('ABSPATH')) { exit; }
function slovenka_page() {
    // A taxonomy's queried object ID belongs to the terms table, never postmeta.
    if (!is_singular('page')) { return null; }
    $file = get_post_meta(get_queried_object_id(), '_slovenka_source', true);
    return slovenka_data()['pages'][$file] ?? null;
}
function slovenka_language() {
    return slovenka_page()['lang'] ?? (str_starts_with(determine_locale(), 'bs') ? 'bs' : 'en');
}
function slovenka_text($bosnian, $english) {
    return slovenka_language() === 'bs' ? $bosnian : $english;
}
function slovenka_page_url($file) {
    $id = (int)(get_option('slovenka_pages', [])[$file] ?? 0);
    return $id && get_post_status($id) === 'publish' ? get_permalink($id) : '';
}
function slovenka_primary_fallback() {
    echo '<ul class="primary-menu">';
    $links = [[home_url('/'), slovenka_text('Početna','Home')]];
    if (function_exists('wc_get_page_permalink')) { $links[] = [wc_get_page_permalink('shop'), slovenka_text('Trgovina','Shop')]; }
    foreach (['company'=>['O nama','Our company'], 'manufacturing'=>['Proizvodnja','Manufacturing'], 'contact'=>['Kontakt','Contact']] as $name=>$label) {
        $url = slovenka_page_url($name . (slovenka_language() === 'en' ? '-en' : '') . '.html');
        if ($url) { $links[] = [$url, slovenka_text(...$label)]; }
    }
    foreach ($links as [$url,$label]) { echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>'; }
    echo '</ul>';
}
function slovenka_category_links() {
    if (!taxonomy_exists('product_cat')) { return; }
    $terms = get_terms(['taxonomy'=>'product_cat', 'hide_empty'=>true]);
    foreach (is_wp_error($terms) ? [] : $terms as $term) {
        $url = get_term_link($term);
        if (!is_wp_error($url)) { echo '<a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a>'; }
    }
}
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
