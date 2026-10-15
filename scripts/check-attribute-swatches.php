<?php
/** Exercise saved types and shades on a disposable localhost WooCommerce site. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1','localhost'], true)) { throw new RuntimeException('Disposable localhost only.'); }
function swatch_check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
$attribute_ids = []; $product_id = 0; $saved_post = $_POST; $saved_user = get_current_user_id();
$admin = get_users(['role'=>'administrator','number'=>1,'fields'=>'ID'])[0];
wp_set_current_user($admin);
try {
    $suffix = strtolower(wp_generate_password(6, false));
    $definitions = [];
    foreach (['qa-finish-'=>'slovenka_color','qa-fit-'=>'slovenka_size'] as $prefix=>$type) {
        $id = wc_create_attribute(['name'=>$prefix . $suffix,'slug'=>$prefix . $suffix,'type'=>$type]);
        swatch_check(!is_wp_error($id), 'Attribute creation failed'); $attribute_ids[] = $id;
        $definition = wc_get_attribute($id); $taxonomy = $definition->slug;
        register_taxonomy($taxonomy, ['product'], ['public'=>true,'capabilities'=>['edit_terms'=>'manage_product_terms','manage_terms'=>'manage_product_terms','delete_terms'=>'manage_product_terms','assign_terms'=>'assign_product_terms']]);
        swatch_check($definition->type === $type, 'Native attribute type not retained');
        $definitions[] = [$id,$taxonomy];
    }
    [$color_id,$color_taxonomy] = $definitions[0]; [$size_id,$size_taxonomy] = $definitions[1];
    $term_id = wp_insert_term('Unexpected label', $color_taxonomy, ['slug'=>'custom-option'])['term_id'];
    $size_term_id = wp_insert_term('Custom size', $size_taxonomy, ['slug'=>'custom-size'])['term_id'];
    $_POST = ['slovenka_term_color_nonce'=>wp_create_nonce('slovenka_term_color_' . $color_taxonomy),'slovenka_term_color'=>'#1258a2'];
    slovenka_save_term_color($term_id, $color_taxonomy);
    swatch_check(get_term_meta($term_id, '_slovenka_swatch_color', true) === '#1258a2', 'Shade not saved');
    foreach (['not-a-color', ['#ffffff']] as $invalid) {
        $_POST['slovenka_term_color'] = $invalid; slovenka_save_term_color($term_id, $color_taxonomy);
        swatch_check(get_term_meta($term_id, '_slovenka_swatch_color', true) === '#1258a2', 'Invalid input replaced saved shade');
    }
    $_POST['slovenka_term_color'] = '#ffffff'; $_POST['slovenka_term_color_nonce'] = 'invalid';
    slovenka_save_term_color($term_id, $color_taxonomy);
    swatch_check(get_term_meta($term_id, '_slovenka_swatch_color', true) === '#1258a2', 'Bad nonce accepted');
    $_POST['slovenka_term_color_nonce'] = wp_create_nonce('slovenka_term_color_' . $color_taxonomy);
    wp_set_current_user(0); slovenka_save_term_color($term_id, $color_taxonomy); wp_set_current_user($admin);
    swatch_check(get_term_meta($term_id, '_slovenka_swatch_color', true) === '#1258a2', 'Unauthenticated write accepted');
    $product = new WC_Product_Variable(); $product->set_name('Temporary attribute shade verification'); $product->set_status('draft');
    $attributes = [];
    foreach ([[$color_id,$color_taxonomy,$term_id],[$size_id,$size_taxonomy,$size_term_id]] as [$id,$taxonomy,$term]) {
        $attr = new WC_Product_Attribute(); $attr->set_id($id); $attr->set_name($taxonomy); $attr->set_options([$term]); $attr->set_visible(true); $attr->set_variation(true); $attributes[] = $attr;
    }
    $local = new WC_Product_Attribute(); $local->set_name('Arbitrary local name'); $local->set_options(['Mixed Case']); $local->set_variation(true); $attributes[] = $local;
    $product->set_attributes($attributes); $product_id = $product->save();
    swatch_check(slovenka_swatch_config($product,$color_taxonomy) === ['type'=>'color','colors'=>['custom-option'=>'#1258a2']], 'Saved term shade not used');
    swatch_check(slovenka_swatch_config($product,$size_taxonomy)['type'] === 'size', 'Size type depends on naming');
    wp_update_term($term_id,$color_taxonomy,['name'=>'Renamed term']);
    swatch_check(slovenka_swatch_config($product,$color_taxonomy)['colors']['custom-option'] === '#1258a2', 'Renaming changed shade');
    wc_update_attribute($color_id,['type'=>'select']);
    swatch_check(slovenka_swatch_config($product,$color_taxonomy)['type'] === '', 'Explicit dropdown ignored');
    wc_update_attribute($color_id,['type'=>'slovenka_color']);
    $_POST['slovenka_term_color'] = ''; slovenka_save_term_color($term_id,$color_taxonomy);
    swatch_check(!slovenka_swatch_config($product,$color_taxonomy)['colors'], 'Clear did not remove shade');
    set_current_screen('product');
    $_POST = ['attribute_names'=>[0=>'Arbitrary local name'],'slovenka_swatches_nonce'=>wp_create_nonce('slovenka_product_swatches_' . $product_id), 'slovenka_swatches'=>[0=>['type'=>'color','colors'=>[['value'=>'Mixed Case','color'=>'#a13f76']]]]];
    $product->save(); $product = wc_get_product($product_id);
    swatch_check(slovenka_swatch_config($product,'Arbitrary local name') === ['type'=>'color','colors'=>['Mixed Case'=>'#a13f76']], 'Local explicit settings not saved');
    $_POST['slovenka_swatches'][0]['colors'][0]['color'] = 'invalid'; $product->save();
    swatch_check(slovenka_swatch_config($product,'Arbitrary local name')['colors']['Mixed Case'] === '#a13f76', 'Invalid local replacement lost shade');
    $_POST['slovenka_swatches'][0]['type'] = 'size'; $product->save();
    swatch_check(slovenka_swatch_config($product,'Arbitrary local name')['type'] === 'size', 'Local size setting ignored');
    $_POST['slovenka_swatches'][0]['type'] = ''; $product->save();
    swatch_check(slovenka_swatch_config($product,'Arbitrary local name')['type'] === '', 'Local dropdown setting ignored');
    echo "PASS: native attribute type persistence, arbitrary names, term shade save/clear/rename, invalid input/nonce/permission protection, local attribute settings and exact-case colours\n";
} finally {
    $_POST = $saved_post; wp_set_current_user($admin);
    if ($product_id) { wc_get_product($product_id)->delete(true); }
    foreach ($attribute_ids as $id) { wc_delete_attribute($id); }
    wp_set_current_user($saved_user);
}
