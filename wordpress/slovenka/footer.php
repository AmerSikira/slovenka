<?php if (!defined('ABSPATH')) { exit; } ?>
<footer class="footer dark"><div class="container">
<div class="footer-top"><a class="wordmark footer-wordmark" href="<?php echo esc_url(slovenka_home_url()); ?>"><?php bloginfo('name'); ?></a>
<p><?php echo esc_html(slovenka_text('Više od odjeće.','Considered clothing.')); ?><br><?php echo esc_html(slovenka_text('Dizajn koji inspiriše. Kvalitet koji traje.','A closer look at every cut.')); ?></p>
<?php $contact = slovenka_page_url('contact' . (slovenka_language() === 'en' ? '-en' : '') . '.html'); if ($contact) { ?><a class="text-link" href="<?php echo esc_url($contact); ?>"><?php echo esc_html(slovenka_text('Stupimo u kontakt','Get in touch')); ?> ↗</a><?php } ?>
</div>
<?php if (has_nav_menu('footer')) { wp_nav_menu(['theme_location'=>'footer','container'=>'nav','container_aria_label'=>slovenka_text('Navigacija podnožja','Footer navigation'),'menu_class'=>'native-footer-menu']); } else { ?>
<div class="footer-groups">
<?php if (function_exists('wc_get_page_permalink')) { ?><details open><summary><?php echo esc_html(slovenka_text('Trgovina','Shop')); ?></summary><div><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html(slovenka_text('Sva odjeća','Shop all')); ?></a><?php slovenka_category_links(); ?></div></details><?php } ?>
<?php foreach ([['Podrška kupcima','Customer support',['contact','support-delivery','support-returns','support-sizing']],['Kompanija','Company',['company','support-privacy','support-terms']],['Proizvodnja','Manufacturing',['manufacturing','inquiry']]] as [$bs,$en,$files]) { ?>
<details open><summary><?php echo esc_html(slovenka_text($bs,$en)); ?></summary><div>
<?php foreach ($files as $file) {
    $id = get_option('slovenka_pages', [])[$file . (slovenka_language() === 'en' ? '-en' : '') . '.html'] ?? 0;
    if ($id && get_post_status($id) === 'publish') { echo '<a href="' . esc_url(get_permalink($id)) . '">' . esc_html(get_the_title($id)) . '</a>'; }
} ?></div></details><?php } ?>
</div><?php } ?>
<div class="footer-bottom"><span>© <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></span><?php the_privacy_policy_link(); ?></div>
</div></footer>
<?php if (function_exists('woocommerce_mini_cart') && WC()->cart) { ?>
<dialog id="site-cart" class="native-cart" aria-labelledby="site-cart-title">
<div class="modal-head"><h2 id="site-cart-title"><?php echo esc_html(slovenka_text('Vaša korpa','Your cart')); ?></h2><button type="button" class="icon-button" data-close-cart aria-label="<?php echo esc_attr(slovenka_text('Zatvori korpu','Close cart')); ?>">×</button></div>
<div class="native-cart-content widget_shopping_cart"><div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div></div>
<button type="button" class="underlined native-cart-continue" data-close-cart><?php echo esc_html(slovenka_text('Nastavi razgledati','Continue browsing')); ?></button>
</dialog>
<?php } ?>
<?php wp_footer(); ?>
</body></html>
