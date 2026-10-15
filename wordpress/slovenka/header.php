<?php
if (!defined('ABSPATH')) { exit; }
$page = slovenka_page();
$fallback = slovenka_data()['pages']['index.html'];
$head = $page['head'] ?? $fallback['head'];
if (!$page) {
    $head = preg_replace('~<title>.*?</title>~s', '<title>' . esc_html(wp_get_document_title()) . '</title>', $head);
    $head = preg_replace('~<meta\b[^>]*name="description"[^>]*>|<link\b[^>]*rel="alternate"[^>]*>~i', '', $head);
    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product) { $head .= '<meta name="description" content="' . esc_attr(wp_strip_all_tags($product->get_short_description() ?: $product->get_description())) . '">'; }
    }
}
?><!doctype html>
<html lang="<?php echo esc_attr($page['lang'] ?? 'bs'); ?>"><head>
<script>window.SlovenkaSite = <?php echo wp_json_encode(slovenka_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php echo slovenka_html($head); ?>
<script defer src="<?php echo esc_url(add_query_arg('ver', wp_get_theme()->get('Version'), get_template_directory_uri() . '/production.js')); ?>"></script>
<?php wp_head(); ?>
</head><body <?php body_class(); ?> data-page="<?php echo esc_attr($page['route'] ?? '/woocommerce'); ?>">
<?php wp_body_open(); echo slovenka_html($page['before'] ?? $fallback['before']); ?>
