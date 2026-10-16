<?php
if (!defined('ABSPATH')) { exit; }

// Let a dedicated SEO plugin own metadata when one is installed.
function slovenka_seo_enabled() {
    $plugin = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION');
    return (bool)apply_filters('slovenka_seo_enabled', !$plugin);
}
function slovenka_seo_canonical() {
    if (is_404()) { return ''; }
    if (is_front_page()) { $url = home_url('/'); }
    elseif (is_singular()) { $url = wp_get_canonical_url() ?: get_permalink(); }
    elseif (function_exists('is_shop') && is_shop()) { $url = get_post_type_archive_link('product'); }
    elseif (is_category() || is_tag() || is_tax()) { $url = get_term_link(get_queried_object()); }
    elseif (is_home()) { $url = get_permalink((int)get_option('page_for_posts')) ?: home_url('/'); }
    else { $url = slovenka_current_url(); }
    if (is_wp_error($url)) { return ''; }
    if (get_query_var('paged') > 1) { $url = get_pagenum_link((int)get_query_var('paged'), false); }
    $parts = wp_parse_url($url);
    parse_str($parts['query'] ?? '', $query);
    $keep = ['p','page_id','product','post_type','product_cat','product_tag','taxonomy','term','cat','paged','page','author','m','year','monthnum','day','name','pagename','s'];
    $query = array_intersect_key($query, array_flip($keep));
    $clean = strtok($url, '?#');
    if ($query) { $clean = add_query_arg($query, $clean); }
    return slovenka_language_url($clean);
}
function slovenka_seo_description() {
    $text = '';
    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product) { $text = $product->get_short_description() ?: $product->get_description(); }
    } elseif (is_singular()) {
        $post = get_queried_object();
        $text = $post->post_excerpt ?: $post->post_content;
    } elseif (is_category() || is_tag() || is_tax()) {
        $text = get_queried_object()->description;
    } elseif (function_exists('is_shop') && is_shop()) {
        $text = slovenka_text('Istražite kolekciju MK Slovenka. Ženska odjeća, sakoi, hlače, jakne i kaputi iz Donjeg Vakufa.', 'Explore the MK Slovenka collection. Womenswear, blazers, trousers, jackets and coats from Donji Vakuf.');
    }
    if (!$text) { $text = get_bloginfo('description') ?: slovenka_text('MK Slovenka — modna, radna i profesionalna odjeća iz Donjeg Vakufa.', 'MK Slovenka — fashion, workwear and professional clothing from Donji Vakuf.'); }
    $text = preg_replace('~</(?:p|div|h[1-6]|li|section)>~i', ' ', $text);
    $text = html_entity_decode(wp_strip_all_tags(strip_shortcodes($text)), ENT_QUOTES, get_bloginfo('charset'));
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if (mb_strlen($text) > 160) { $text = preg_replace('/\s+\S*$/u', '', mb_substr($text, 0, 157)) . '…'; }
    return apply_filters('slovenka_seo_description', $text);
}
function slovenka_seo_image() {
    $id = is_singular() ? get_post_thumbnail_id(get_queried_object_id()) : 0;
    if (!$id && is_tax()) { $id = (int)get_term_meta(get_queried_object_id(), 'thumbnail_id', true); }
    if (!$id) { $id = (int)get_theme_mod('custom_logo'); }
    if ($id) {
        $image = wp_get_attachment_image_src($id, 'full');
        if ($image) { return ['url'=>$image[0], 'width'=>$image[1], 'height'=>$image[2], 'alt'=>get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_bloginfo('name')]; }
    }
    // Use an existing editorial photograph, never an invented product image.
    $front = get_post((int)get_option('page_on_front'));
    $content = is_singular() ? get_post_field('post_content', get_queried_object_id()) : ($front->post_content ?? '');
    $html = new WP_HTML_Tag_Processor($content);
    while ($html->next_tag('img')) {
        $url = $html->get_attribute('src');
        if (!is_string($url) || !$url || str_contains($url, '/flags/')) { continue; }
        if (str_starts_with($url, 'assets/')) { $url = get_theme_file_uri($url); }
        if (str_starts_with($url, '/')) { $url = home_url($url); }
        if (in_array(wp_parse_url($url, PHP_URL_SCHEME), ['https','http'], true)) { return ['url'=>$url, 'alt'=>$html->get_attribute('alt') ?: get_bloginfo('name')]; }
    }
    return [];
}
function slovenka_language_alternates() {
    // Only advertise translations that exist as published, authored pages.
    $page = slovenka_page();
    if (!$page) { return []; }
    $source = get_post_meta(get_queried_object_id(), '_slovenka_source', true);
    $bs = str_replace('-en.html', '.html', $source);
    $en = str_replace('.html', '-en.html', $bs);
    $urls = [];
    foreach (['bs'=>$bs,'en'=>$en] as $lang=>$file) {
        $url = slovenka_page_url($file);
        if (!$url) { return []; }
        $urls[$lang] = slovenka_language_url($url, $lang);
    }
    $urls['x-default'] = $urls[slovenka_default_language()];
    return $urls;
}
function slovenka_private_commerce_content($content) {
    foreach (['woocommerce_cart','woocommerce_checkout','woocommerce_my_account'] as $shortcode) { if (has_shortcode($content, $shortcode)) { return true; } }
    return has_block('woocommerce/cart', $content) || has_block('woocommerce/checkout', $content);
}
function slovenka_seo_private_page() {
    if (is_search() || is_404() || is_preview() || post_password_required()) { return true; }
    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) { return true; }
    if (is_singular()) {
        $content = get_post_field('post_content', get_queried_object_id());
        if (slovenka_private_commerce_content($content)) { return true; }
    }
    foreach (array_keys($_GET) as $key) {
        if (preg_match('/^(?:filter_|query_type_|attribute_)/', $key) || in_array($key, ['orderby','min_price','max_price','rating_filter','add-to-cart','remove_item'], true)) { return true; }
    }
    // The shared catalogue translates controls, not merchant-authored product copy.
    return function_exists('is_product') && is_product() && slovenka_language() !== slovenka_default_language();
}
add_filter('wp_robots', function ($robots) {
    if (slovenka_seo_enabled() && slovenka_seo_private_page()) { unset($robots['index']); $robots['noindex'] = true; }
    return $robots;
});
add_filter('document_title_parts', function ($parts) {
    if (!slovenka_seo_enabled()) { return $parts; }
    if (is_front_page()) {
        $parts['title'] = slovenka_text('Ženska odjeća i proizvodnja odjeće', 'Womenswear and clothing production');
        $parts['site'] = get_bloginfo('name');
        unset($parts['tagline']);
    }
    if (function_exists('is_shop') && is_shop() && !is_search()) { $parts['title'] = slovenka_text('Trgovina','Shop'); }
    return $parts;
});
add_action('wp', function () {
    if (slovenka_seo_enabled()) { remove_action('wp_head', 'rel_canonical'); }
});
add_action('wp_head', function () {
    if (!slovenka_seo_enabled() || is_feed()) { return; }
    $canonical = slovenka_seo_canonical();
    $description = slovenka_seo_description();
    $title = html_entity_decode(wp_get_document_title(), ENT_QUOTES, get_bloginfo('charset'));
    $image = slovenka_seo_image();
    $locale = slovenka_language() === 'bs' ? 'bs_BA' : 'en_US';
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    if ($canonical) { echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n"; }
    foreach (slovenka_language_alternates() as $lang=>$url) { echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '">' . "\n"; }
    if (slovenka_language_alternates()) { echo '<meta property="og:locale:alternate" content="' . ($locale === 'bs_BA' ? 'en_US' : 'bs_BA') . '">' . "\n"; }
    $tags = ['og:type'=>is_singular('post') ? 'article' : 'website','og:site_name'=>get_bloginfo('name'),'og:title'=>$title,'og:description'=>$description,'og:locale'=>$locale];
    if ($canonical) { $tags['og:url'] = $canonical; }
    if ($image) {
        $tags['og:image'] = $image['url']; $tags['og:image:alt'] = $image['alt'];
        if (!empty($image['width'])) { $tags['og:image:width'] = $image['width']; $tags['og:image:height'] = $image['height']; }
    }
    foreach ($tags as $name=>$value) { echo '<meta property="' . esc_attr($name) . '" content="' . esc_attr($value) . '">' . "\n"; }
    echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
    foreach (['title'=>$title,'description'=>$description,'image'=>$image['url'] ?? '','image:alt'=>$image['alt'] ?? ''] as $key=>$value) {
        if ($value !== '') { echo '<meta name="twitter:' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n"; }
    }
    if (is_singular('post')) { echo '<meta property="article:published_time" content="' . esc_attr(get_post_time('c', true)) . '"><meta property="article:modified_time" content="' . esc_attr(get_post_modified_time('c', true)) . '">' . "\n"; }
    if (!$canonical || slovenka_seo_private_page()) { return; }
    $root = home_url('/'); $organization = $root . '#organization'; $website = $root . '#website';
    $org = ['@type'=>'Organization','@id'=>$organization,'name'=>get_bloginfo('name'),'url'=>$root];
    $logo = wp_get_attachment_image_url((int)get_theme_mod('custom_logo'), 'full');
    if ($logo) { $org['logo'] = ['@type'=>'ImageObject','url'=>$logo]; }
    $webpage = ['@type'=>is_archive() ? 'CollectionPage' : 'WebPage','@id'=>$canonical . '#webpage','url'=>$canonical,'name'=>$title,'description'=>$description,'inLanguage'=>str_replace('_','-',$locale),'isPartOf'=>['@id'=>$website],'publisher'=>['@id'=>$organization]];
    if ($image) { $webpage['primaryImageOfPage'] = ['@type'=>'ImageObject','url'=>$image['url']]; }
    if (function_exists('is_product') && is_product()) { $webpage['mainEntity'] = ['@id'=>$canonical . '#product']; }
    $graph = [$org, ['@type'=>'WebSite','@id'=>$website,'url'=>$root,'name'=>get_bloginfo('name'),'publisher'=>['@id'=>$organization],'inLanguage'=>['bs-BA','en-US']], $webpage];
    if (is_singular('post')) {
        $author = (int)get_post_field('post_author', get_queried_object_id());
        $article = ['@type'=>'Article','@id'=>$canonical . '#article','headline'=>get_the_title(),'datePublished'=>get_post_time('c',true),'dateModified'=>get_post_modified_time('c',true),'author'=>['@type'=>'Person','name'=>get_the_author_meta('display_name',$author),'url'=>get_author_posts_url($author)],'publisher'=>['@id'=>$organization],'mainEntityOfPage'=>['@id'=>$canonical . '#webpage'],'inLanguage'=>str_replace('_','-',$locale)];
        if ($image) { $article['image'] = [$image['url']]; }
        $graph[] = $article;
    }
    if (!(function_exists('is_product') && is_product()) && $canonical !== slovenka_home_url()) {
        $crumbs = [['@type'=>'ListItem','position'=>1,'name'=>slovenka_text('Početna','Home'),'item'=>slovenka_home_url()]];
        if (is_tax() && function_exists('wc_get_page_permalink')) { $crumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>slovenka_text('Trgovina','Shop'),'item'=>wc_get_page_permalink('shop')]; }
        $crumbs[] = ['@type'=>'ListItem','position'=>count($crumbs)+1,'name'=>is_tax() ? single_term_title('',false) : (is_singular() ? get_the_title() : $title),'item'=>$canonical];
        $graph[] = ['@type'=>'BreadcrumbList','@id'=>$canonical . '#breadcrumb','itemListElement'=>$crumbs];
    }
    echo '<script type="application/ld+json" class="slovenka-schema">' . wp_json_encode(['@context'=>'https://schema.org','@graph'=>$graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>' . "\n";
}, 5);
// WooCommerce remains the source of truth for product prices, currency and stock.
add_filter('woocommerce_structured_data_product', function ($data, $product) {
    if (!slovenka_seo_enabled()) { return $data; }
    $url = slovenka_language_url($product->get_permalink());
    $data['@id'] = $url . '#product'; $data['url'] = $url;
    $data['mainEntityOfPage'] = ['@id'=>$url . '#webpage'];
    unset($data['aggregateRating'], $data['review']); // Reviews are hidden by this theme.
    return $data;
}, 20, 2);
add_filter('woocommerce_structured_data_website', function ($data) { return slovenka_seo_enabled() ? [] : $data; });
// Keep WordPress core XML sitemaps; exclude private commerce destinations.
add_filter('wp_sitemaps_posts_query_args', function ($args, $type) {
    if (!slovenka_seo_enabled() || $type !== 'page' || !function_exists('wc_get_page_id')) { return $args; }
    $exclude = array_filter(array_map('wc_get_page_id', ['cart','checkout','myaccount']), static fn($id)=>$id > 0);
    foreach (get_option('slovenka_pages', []) as $file=>$id) {
        if (preg_match('/^(?:(?:cart|checkout|order)(?:-en)?|product-.+|shop-en)\.html$/', $file)) { $exclude[] = (int)$id; }
    }
    foreach (get_posts(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,'no_found_rows'=>true]) as $page) {
        if (slovenka_private_commerce_content($page->post_content)) { $exclude[] = $page->ID; }
    }
    $args['post__not_in'] = array_unique(array_merge($args['post__not_in'] ?? [], $exclude));
    return $args;
}, 10, 2);
