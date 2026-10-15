#!/usr/bin/env python3
"""Read-only checks for the native shop heading and category introductions."""
import argparse
import subprocess
import urllib.parse
import urllib.request

from bs4 import BeautifulSoup

parser = argparse.ArgumentParser()
parser.add_argument('--wp', required=True)
parser.add_argument('--path', required=True)
parser.add_argument('--url', default='http://127.0.0.1:5196')
args = parser.parse_args()
assert urllib.parse.urlsplit(args.url).hostname in ['127.0.0.1', 'localhost']

def read(params):
    url = args.url.rstrip('/') + '/?' + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url) as response:
        return BeautifulSoup(response.read(), 'html.parser')

for lang, heading in [('bs', 'Trgovina'), ('en', 'Shop')]:
    doc = read({'post_type': 'product', 'lang': lang})
    header = doc.select_one('.woocommerce-products-header')
    assert header and header.get_text(strip=True) == heading, str(header)
    assert not doc.select('.page-description'), 'Shop introduction is visible'
    assert not doc.select('.woocommerce-breadcrumb'), 'Shop breadcrumb is visible'
    assert doc.select('.woocommerce-ordering') and doc.select('ul.products > li.product')

command = ['php', '-d', 'error_reporting=22527', args.wp, '--path=' + args.path, 'eval']

# Change only the in-process cached post object, leaving saved shop content intact.
shop_check = r'''
$GLOBALS['wp_query'] = new WP_Query(['post_type'=>'product']);
$shop = clone get_post(wc_get_page_id('shop'));
$shop->post_content = 'EDITED_SHOP_INTRO';
wp_cache_set($shop->ID, $shop, 'posts');
ob_start(); woocommerce_product_archive_description(); $before = ob_get_clean();
if (strpos($before, 'EDITED_SHOP_INTRO') === false) { throw new Exception('Invalid shop content fixture'); }
do_action('wp');
ob_start(); do_action('woocommerce_archive_description'); $after = ob_get_clean();
if (strpos($after, 'EDITED_SHOP_INTRO') !== false || strpos($after, 'page-description') !== false) {
    throw new Exception('Edited shop content is still displayed');
}
echo 'PASS: edited shop content stays hidden without changing saved content.';
'''
print(subprocess.check_output(command + [shop_check], text=True).strip())

category_check = r'''
$terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'number'=>1]);
if (!$terms || is_wp_error($terms)) { throw new Exception('No product category fixture'); }
$term = $terms[0];
$GLOBALS['wp_query'] = new WP_Query(['product_cat'=>$term->slug]);
add_filter('woocommerce_taxonomy_archive_description_raw', fn($description)=>'CATEGORY_INTRO');
do_action('wp');
if (woocommerce_page_title(false) !== $term->name) { throw new Exception('Category title changed'); }
ob_start(); do_action('woocommerce_archive_description'); $description = ob_get_clean();
if (strpos($description, 'CATEGORY_INTRO') === false) { throw new Exception('Category description removed'); }
echo 'PASS: category title and description remain native.';
'''
print(subprocess.check_output(command + [category_check], text=True).strip())
print('PASS: Bosnian/English shop headings, no subtitle, ordering and product loop.')
