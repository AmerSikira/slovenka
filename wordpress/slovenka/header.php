<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php echo esc_html(slovenka_text('Preskoči na sadržaj','Skip to content')); ?></a>
<header class="header">
<div class="header-inner">
<button class="icon-button mobile-menu" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="<?php echo esc_attr(slovenka_text('Otvori meni','Open menu')); ?>">☰</button>
<?php if (has_custom_logo()) { the_custom_logo(); } else { ?>
<a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
<?php } ?>
<nav id="site-navigation" class="desktop-nav" aria-label="<?php esc_attr_e('Primary navigation','slovenka'); ?>">
<?php wp_nav_menu(['theme_location'=>'primary', 'container'=>false, 'menu_class'=>'primary-menu', 'fallback_cb'=>'slovenka_primary_fallback']); ?>
<?php if (taxonomy_exists('product_cat')) { ?>
<details class="native-categories"><summary><?php echo esc_html(slovenka_text('Kategorije','Categories')); ?></summary><div><?php slovenka_category_links(); ?></div></details>
<?php } ?>
</nav>
<div class="utilities">
<?php $page = slovenka_page(); if ($page) {
    $file = get_post_meta(get_queried_object_id(), '_slovenka_source', true);
    $alternate = $page['lang'] === 'en' ? str_replace('-en.html','.html',$file) : str_replace('.html','-en.html',$file);
    $url = slovenka_page_url($alternate);
    if ($url) { ?>
<a class="language language-flag" href="<?php echo esc_url($url); ?>" hreflang="<?php echo $page['lang'] === 'en' ? 'bs' : 'en'; ?>" aria-label="<?php echo esc_attr($page['lang'] === 'en' ? 'Bosanski' : 'English'); ?>"><img src="<?php echo esc_url(get_theme_file_uri('assets/flags/' . ($page['lang'] === 'en' ? 'ba' : 'gb') . '.svg')); ?>" width="28" height="14" alt=""></a>
<?php } } ?>
<button type="button" class="icon-button" data-open-search aria-controls="site-search" aria-label="<?php echo esc_attr(slovenka_text('Pretraga','Search')); ?>"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/></svg></button>
<?php if (function_exists('wc_get_cart_url')) { ?>
<a class="bag-trigger" href="<?php echo esc_url(wc_get_cart_url()); ?>"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 7h12l2 14H4L6 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg><span class="bag-label"><?php echo esc_html(slovenka_text('Korpa','Cart')); ?></span></a>
<?php } ?>
</div></div></header>
<dialog id="site-search" class="native-search"><div class="modal-head"><h2><?php echo esc_html(slovenka_text('Pretraga','Search')); ?></h2><button type="button" class="icon-button" data-close-search aria-label="<?php esc_attr_e('Close','slovenka'); ?>">×</button></div><?php get_search_form(); ?></dialog>
