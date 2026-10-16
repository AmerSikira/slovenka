<?php
if (!defined('ABSPATH')) { exit; }
// Imported commerce pages contain native shortcodes/blocks, not editorial wrappers.
$commerce = function_exists('is_cart') && (is_cart() || is_checkout()
    || has_shortcode(get_post_field('post_content', get_queried_object_id()), 'woocommerce_cart')
    || has_shortcode(get_post_field('post_content', get_queried_object_id()), 'woocommerce_checkout')
    || has_block('woocommerce/cart') || has_block('woocommerce/checkout'));
$editorial = !$commerce && get_post_meta(get_queried_object_id(), '_slovenka_source', true);
get_header(); ?>
<main id="main" class="<?php echo $editorial ? 'editorial-content' : ($commerce ? 'container native-commerce-page' : 'container section-space'); ?>">
<?php while (have_posts()) { the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php if (!$editorial) { the_title('<h1 class="entry-title">','</h1>'); } ?>
<div class="entry-content"><?php the_content(); wp_link_pages(['before'=>'<nav class="page-links">','after'=>'</nav>']); ?></div>
</article>
<?php if (comments_open() || get_comments_number()) { comments_template(); } ?>
<?php } ?>
</main>
<?php get_footer(); ?>
