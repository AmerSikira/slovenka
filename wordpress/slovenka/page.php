<?php
if (!defined('ABSPATH')) { exit; }
get_header(); ?>
<main id="main" class="<?php echo get_post_meta(get_queried_object_id(), '_slovenka_source', true) ? 'editorial-content' : 'container section-space'; ?>">
<?php while (have_posts()) { the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php if (!get_post_meta(get_the_ID(), '_slovenka_source', true)) { the_title('<h1 class="entry-title">','</h1>'); } ?>
<div class="entry-content"><?php the_content(); wp_link_pages(['before'=>'<nav class="page-links">','after'=>'</nav>']); ?></div>
</article>
<?php if (comments_open() || get_comments_number()) { comments_template(); } ?>
<?php } ?>
</main>
<?php get_footer(); ?>
