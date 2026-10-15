<?php
if (!defined('ABSPATH')) { exit; }
$page = slovenka_page();
echo slovenka_html($page['after'] ?? slovenka_data()['pages']['index.html']['after']);
wp_footer();
?></body></html>
