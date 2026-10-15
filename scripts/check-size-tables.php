<?php
/** Run with wp eval-file on a disposable localhost store; pass ALBA, ARIA and COCO XLSX paths. */
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['localhost','127.0.0.1'], true)) throw new RuntimeException('Disposable localhost WP-CLI test only.');
$argv = array_merge([__FILE__], $args);
function mk_size_check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
mk_size_check(count($argv) === 4, 'Pass the ALBA, ARIA and COCO fixture paths.');
$expected = [
    [['36','76','97','98.5'],['38','80','101','99'],['40','84','105','99.5'],['42','89','109','100'],['44','94','113','100.5'],['46','99','117','101']],
    [['36','77','99','113.5'],['38','81','103','114'],['40','85','107','114.5'],['42','90','111','115'],['44','95','115','115.5'],['46','100','119','116']],
    [['36','46','39','48','59','65','37.5'],['38','48','41','50','60','65.5','38.5'],['40','50','43','52','60','66','39.5'],['42','52','45','54','61','66.5','41'],['44','54','47','56','61','67','42'],['46','56','50','58','62','67.5','43'],['48','59','53','61','62','68','45'],['50','62','57','64','63','68.5','47']],
];
$imports = [];
foreach (array_slice($argv, 1) as $i => $path) {
    $data = mk_import_size_table($path);
    mk_size_check(!is_wp_error($data), is_wp_error($data) ? $data->get_error_message() : '');
    mk_size_check(count($data) === 1 && $data[0]['rows'] === $expected[$i], 'Imported values differ from source fixture ' . $i);
    $imports[] = $data;
}
mk_size_check($imports[2][0]['headers'][1] === 'O GRUDI/2', 'Half-measurement label changed.');
mk_size_check(is_wp_error(mk_import_size_table(__FILE__)), 'Non-XLSX accepted.');
mk_size_check(is_wp_error(mk_import_size_table('/missing.xlsx')), 'Missing file accepted.');
$ids = [];
$copy = tempnam(sys_get_temp_dir(), 'mk-size-test-');
try {
    // Mutate a disposable fixture to verify formula/cache and XML rejection paths.
    copy($argv[3], $copy);
    $zip = new ZipArchive(); $zip->open($copy);
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $xml = preg_replace('/(<f[^>]*>.*?<\/f>)\s*<v>.*?<\/v>/s', '$1', $xml, 1, $count);
    mk_size_check($count === 1, 'Formula fixture not found.');
    $zip->addFromString('xl/worksheets/sheet1.xml', $xml); $zip->close();
    mk_size_check(is_wp_error(mk_import_size_table($copy)), 'Uncached formula accepted.');
    $zip->open($copy);
    $zip->addFromString('xl/workbook.xml', '<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>'); $zip->close();
    mk_size_check(is_wp_error(mk_import_size_table($copy)), 'XML entity declaration accepted.');
    for ($i = 0; $i < 2; $i++) {
        $id = wp_insert_post(['post_type'=>'mk_size_table', 'post_title'=>'Temporary size table QA', 'post_status'=>'publish']);
        $ids[] = $id;
        ob_start();
        mk_size_import_box(get_post($id));
        $empty_preview = ob_get_clean();
        mk_size_check(str_contains($empty_preview, 'mk_size_file'), 'New table upload form did not render.');
        update_post_meta($id, '_mk_size_tables', $imports[$i]);
    }
    $table_ids = $ids;
    for ($i = 0; $i < 2; $i++) {
        $id = wp_insert_post(['post_type'=>'product', 'post_title'=>'Temporary sizing QA', 'post_status'=>'draft']);
        $ids[] = $id;
        update_post_meta($id, '_mk_size_table_ids', $table_ids);
        mk_size_check(count(mk_product_size_tables($id)) === 2, 'Multiple/shared assignment failed.');
    }
    $admin = get_users(['role'=>'administrator', 'number'=>1])[0];
    wp_set_current_user($admin->ID);
    $product_id = end($ids);
    $_POST = ['mk_product_sizes_nonce'=>wp_create_nonce('mk_product_sizes_' . $product_id), 'mk_size_table_ids'=>[$table_ids[0], $table_ids[0], $product_id]];
    wp_update_post(['ID'=>$product_id, 'post_title'=>'Temporary sizing save QA']);
    mk_size_check(get_post_meta($product_id, '_mk_size_table_ids', true) === [$table_ids[0]], 'Assignment validation failed.');
    $_POST = [];
    wp_update_post(['ID'=>$product_id, 'post_title'=>'Temporary sizing no nonce QA']);
    mk_size_check(count(mk_product_size_tables($product_id)) === 1, 'Missing nonce changed assignments.');
    wp_set_current_user(0);
    $_POST = ['mk_product_sizes_nonce'=>wp_create_nonce('mk_product_sizes_' . $product_id)];
    wp_update_post(['ID'=>$product_id, 'post_title'=>'Temporary sizing permission QA']);
    mk_size_check(count(mk_product_size_tables($product_id)) === 1, 'Unauthorized save changed assignments.');
    wp_set_current_user($admin->ID);
    wp_trash_post($table_ids[0]);
    mk_size_check(count(mk_product_size_tables($product_id)) === 0, 'Trashed table leaked to storefront.');
    wp_untrash_post($table_ids[0]);
    $_POST = ['mk_product_sizes_nonce'=>wp_create_nonce('mk_product_sizes_' . $product_id)];
    wp_update_post(['ID'=>$product_id, 'post_title'=>'Temporary sizing cleared QA']);
    mk_size_check(get_post_meta($product_id, '_mk_size_table_ids', true) === [], 'Clearing assignments failed.');
    echo "PASS: all source values, both layouts, cached formulas, invalid files/XML, shared/multiple assignments, nonce/permission protection, trash filtering and clearing.\n";
} finally {
    $_POST = [];
    foreach (array_reverse($ids) as $id) wp_delete_post($id, true);
    unlink($copy);
}
