<?php
if (!defined('ABSPATH')) { exit; }
get_header(); ?>
<main id="main" class="container section-space">
<?php if (is_archive()) { the_archive_title('<h1>','</h1>'); the_archive_description('<div>','</div>'); } ?>
<?php if (is_home()) { ?><h1><?php echo esc_html(single_post_title('', false) ?: slovenka_text('Novosti','News')); ?></h1><?php } ?>
<?php if (is_search()) { ?><h1><?php echo esc_html(sprintf(slovenka_text('Rezultati pretrage za: %s','Search results for: %s'), get_search_query())); ?></h1><?php } ?>
<?php if (have_posts()) { while (have_posts()) { the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class('native-entry'); ?>>
<?php if (is_singular()) { the_title('<h1>','</h1>'); } else { ?><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php } ?>
<div class="entry-content"><?php if (is_singular() || post_password_required()) { the_content(); } else { the_excerpt(); } wp_link_pages(['before'=>'<nav class="page-links">','after'=>'</nav>']); ?></div>
<?php if (is_singular() && (comments_open() || get_comments_number())) { comments_template(); } ?>
</article>
<?php } the_posts_pagination(); } else { ?>
<?php $tag = is_home() || is_search() || is_archive() ? 'h2' : 'h1'; echo '<' . $tag . '>' . esc_html(slovenka_text('Nema rezultata','Nothing found')) . '</' . $tag . '>'; ?>
<?php get_search_form(); } ?>
</main>
<?php get_footer(); ?>
