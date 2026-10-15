<?php
/** Run with wp eval-file on a disposable localhost installation. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
function check_menu_activation($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$locations = get_theme_mod('nav_menu_locations', []);
$keys = ['slovenka_primary_menu','slovenka_primary_menu_seed'];
$saved = []; foreach ($keys as $key) { $saved[$key] = get_option($key, null); }
$created = [];
try {
    check_menu_activation(has_action('after_switch_theme', 'slovenka_setup_primary_menu') !== false, 'Activation callback not registered');
    $custom = wp_create_nav_menu('Activation merchant QA ' . wp_generate_uuid4()); $created[] = $custom;
    wp_update_nav_menu_item($custom, 0, ['menu-item-title'=>'Merchant choice','menu-item-url'=>home_url('/merchant/'),'menu-item-status'=>'publish']);
    set_theme_mod('nav_menu_locations', ['primary'=>$custom, 'footer'=>$custom]);
    $before = wp_get_nav_menu_items($custom);
    slovenka_setup_primary_menu();
    check_menu_activation(get_theme_mod('nav_menu_locations')['primary'] === $custom && wp_get_nav_menu_items($custom) == $before, 'Activation changed assigned merchant menu');
    foreach ($keys as $key) { delete_option($key); }
    set_theme_mod('nav_menu_locations', ['footer'=>$custom]);
    $counts = []; foreach (['page','product','product_variation','attachment'] as $type) { $counts[$type] = wp_count_posts($type); }
    $settings = []; foreach (['woocommerce_currency','woocommerce_shop_page_id','page_on_front','show_on_front'] as $key) { $settings[$key] = get_option($key); }
    slovenka_setup_primary_menu();
    $menu = (int)get_option('slovenka_primary_menu'); $created[] = $menu;
    $items = wp_get_nav_menu_items($menu);
    check_menu_activation(array_column($items, 'title') === ['Početna','Trgovina','O nama','Proizvodnja','Kontakt'], 'Incorrect starter menu');
    check_menu_activation(get_theme_mod('nav_menu_locations')['footer'] === $custom, 'Footer assignment lost');
    foreach ($counts as $type=>$count) { check_menu_activation(wp_count_posts($type) == $count, 'Activation imported ' . $type); }
    foreach ($settings as $key=>$value) { check_menu_activation(get_option($key) === $value, 'Activation changed ' . $key); }
    check_menu_activation($items[1]->object === 'page' && (int)$items[1]->object_id === wc_get_page_id('shop'), 'Shop must link the configured native page');
    slovenka_setup_primary_menu();
    check_menu_activation(array_column(wp_get_nav_menu_items($menu), 'ID') === array_column($items, 'ID'), 'Reactivation duplicated links');
    set_theme_mod('nav_menu_locations', ['footer'=>$custom]);
    slovenka_setup_primary_menu();
    check_menu_activation((int)get_theme_mod('nav_menu_locations')['primary'] === $menu, 'Activation failed to reuse starter menu');
    // Simulate links created before optional editorial setup; only untouched links may be repaired.
    $seed = get_option('slovenka_primary_menu_seed');
    foreach ([$items[2],$items[3]] as $item) {
        wp_update_nav_menu_item($menu, $item->ID, ['menu-item-type'=>'custom','menu-item-title'=>$item->title,'menu-item-object'=>'custom','menu-item-object-id'=>0,'menu-item-url'=>home_url('/missing/'),'menu-item-status'=>'publish','menu-item-position'=>$item->menu_order]);
        $seed[$item->ID]['signature'] = slovenka_menu_item_signature(wp_setup_nav_menu_item(get_post($item->ID)));
    }
    update_option('slovenka_primary_menu_seed', $seed, false);
    wp_update_post(['ID'=>$items[3]->ID,'post_title'=>'Merchant edited title']);
    wp_delete_post($items[4]->ID, true);
    slovenka_refresh_primary_menu_links();
    $repaired = wp_setup_nav_menu_item(get_post($items[2]->ID));
    $edited = wp_setup_nav_menu_item(get_post($items[3]->ID));
    check_menu_activation($repaired->type === 'post_type' && $repaired->object === 'page', 'Starter fallback not resolved to imported page');
    check_menu_activation($edited->type === 'custom' && $edited->title === 'Merchant edited title', 'Merchant edit overwritten');
    check_menu_activation(count(wp_get_nav_menu_items($menu)) === 4, 'Deleted merchant link restored');
    $fallback = slovenka_menu_destination('qa-missing-page.html');
    check_menu_activation($fallback['menu-item-type'] === 'custom' && $fallback['menu-item-url'] === home_url('/qa-missing-page/'), 'Missing-page fallback invalid');
    echo "PASS: activation creates native five-link menu without editorial/product imports; reactivation is idempotent; custom menus/footer preserved; explicit setup resolves untouched fallback links and preserves edits/deletions.\n";
} finally {
    foreach (array_reverse($created) as $id) { if ($id) { wp_delete_nav_menu($id); } }
    set_theme_mod('nav_menu_locations', $locations);
    foreach ($saved as $key=>$value) { if ($value === null) { delete_option($key); } else { update_option($key, $value, false); } }
}
