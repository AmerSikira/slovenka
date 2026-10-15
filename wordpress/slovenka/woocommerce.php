<?php
// Own the WooCommerce shell so the imported shop is not rendered as an archive
// description followed by a second catalogue, heading and fallback sidebar.
if (!defined('ABSPATH')) { exit; }
get_header();
echo '<main id="main">';
slovenka_content();
echo '</main>';
get_footer();
