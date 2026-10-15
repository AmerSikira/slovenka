<?php if (!defined('ABSPATH')) { exit; } ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
<label><span class="screen-reader-text"><?php esc_html_e('Search for:','slovenka'); ?></span><input type="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php echo esc_attr(slovenka_text('Pretražite…','Search…')); ?>" required></label>
<?php if (class_exists('WooCommerce')) { ?><input type="hidden" name="post_type" value="product"><?php } ?>
<button type="submit" class="button"><?php echo esc_html(slovenka_text('Pretraži','Search')); ?></button>
</form>
