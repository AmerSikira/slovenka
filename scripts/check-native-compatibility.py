#!/usr/bin/env python3
"""Exercise real WP/Woo routes on a disposable localhost store, including extension hooks."""
import argparse, json, subprocess, urllib.request, urllib.parse, urllib.error
from pathlib import Path
from bs4 import BeautifulSoup

parser = argparse.ArgumentParser()
parser.add_argument('--wp', required=True)
parser.add_argument('--path', required=True)
parser.add_argument('--url', default='http://127.0.0.1:5196')
a = parser.parse_args()
assert urllib.parse.urlsplit(a.url).hostname in ['127.0.0.1','localhost']
wp_cmd = ['php','-d','error_reporting=22527','-d','memory_limit=512M',a.wp,'--path='+a.path]
def wp(code): return subprocess.check_output(wp_cmd + ['eval',code], text=True).strip()
def read(path='', params=None):
    url = urllib.parse.urljoin(a.url+'/', path)
    if params: url += ('&' if '?' in url else '?') + urllib.parse.urlencode(params)
    response = urllib.request.urlopen(url)
    return BeautifulSoup(response.read(),'html.parser')
def ids(doc):
    return [int(next(c[5:] for c in node.get('class',[]) if c.startswith('post-'))) for node in doc.select('ul.products > li.product')]
root = Path(a.path)
probe = root / 'wp-content/mu-plugins/slovenka-native-audit.php'
assert not probe.exists(), 'An audit is already running'
probe.write_text('''<?php
add_action('init', function () {
    register_block_type('slovenka/audit', ['render_callback'=>fn()=>'<span class="audit-block">DYNAMIC_BLOCK_OK</span>']);
});
add_shortcode('slovenka_audit', fn()=>'<span class="audit-shortcode">SHORTCODE_OK</span>');
add_filter('the_content', function ($content) { return isset($_GET['native_audit']) ? $content . '<span class="audit-content-filter">CONTENT_FILTER_OK</span>' : $content; }, 99);
add_filter('pre_get_document_title', fn($title)=>isset($_GET['native_audit']) ? 'NATIVE_TITLE_OK' : $title);
add_filter('language_attributes', fn($attrs)=>isset($_GET['audit_rtl']) ? 'lang="ar" dir="rtl"' : $attrs, 99);
add_filter('loop_shop_per_page', fn($count)=>isset($_GET['audit_per_page']) ? max(1,absint($_GET['audit_per_page'])) : $count);
add_action('woocommerce_product_query', function ($query) { if (isset($_GET['audit_exclude'])) $query->set('post__not_in',[absint($_GET['audit_exclude'])]); });
foreach (['woocommerce_before_main_content','woocommerce_shop_loop_header','woocommerce_before_shop_loop','woocommerce_shop_loop','woocommerce_before_shop_loop_item','woocommerce_after_shop_loop_item','woocommerce_after_shop_loop','woocommerce_after_main_content','woocommerce_before_single_product','woocommerce_after_single_product'] as $hook) {
    add_action($hook, function () use ($hook) { if (isset($_GET['native_audit'])) echo '<!--AUDIT:' . esc_html($hook) . '-->'; }, 99);
}
''')
snapshot = json.loads(wp('echo wp_json_encode(["options"=>array_combine(["woocommerce_currency","woocommerce_cart_page_id","woocommerce_checkout_page_id","woocommerce_cart_redirect_after_add","posts_per_page"],array_map("get_option",["woocommerce_currency","woocommerce_cart_page_id","woocommerce_checkout_page_id","woocommerce_cart_redirect_after_add","posts_per_page"])),"menus"=>get_theme_mod("nav_menu_locations",[]),"company"=>get_post(get_option("slovenka_pages")["company.html"])->post_content]);'))
created = None
try:
    created = json.loads(wp('''
    $ids=[]; $make=function($title,$content,$type='page') use (&$ids) {$id=wp_insert_post(wp_slash(['post_type'=>$type,'post_title'=>$title,'post_content'=>$content,'post_status'=>'publish','comment_status'=>'open']));$ids[]=$id;return $id;};
    $cart=$make('Merchant cart','[woocommerce_cart]');$checkout=$make('Merchant checkout','[woocommerce_checkout]');
    update_post_meta($cart,'_slovenka_source','cart-en.html');update_post_meta($checkout,'_slovenka_source','checkout-en.html');update_post_meta($cart,'_slovenka_native_content_version','3');update_post_meta($checkout,'_slovenka_native_content_version','3');
    $alias=$make('Legacy checkout alias','[woocommerce_checkout]');update_post_meta($alias,'_slovenka_source','checkout-en.html');
    $account=$make('Account QA','[woocommerce_my_account]');$blockcart=$make('Block cart QA',(new ReflectionMethod('WC_Install','get_cart_block_content'))->invoke(null));$blockcheckout=$make('Block checkout QA',(new ReflectionMethod('WC_Install','get_checkout_block_content'))->invoke(null));
    update_option('woocommerce_cart_page_id',$cart);update_option('woocommerce_checkout_page_id',$checkout);update_option('woocommerce_currency','USD');update_option('woocommerce_cart_redirect_after_add','no');update_option('posts_per_page',1);
    $company=get_option('slovenka_pages')['company.html'];wp_update_post(wp_slash(['ID'=>$company,'post_content'=>get_post_field('post_content',$company).'<!-- wp:shortcode -->[slovenka_audit]<!-- /wp:shortcode --><!-- wp:slovenka/audit /-->']));
    $simple=new WC_Product_Simple();$simple->set_name('Native simple QA');$simple->set_status('publish');$simple->set_regular_price('29');$simple->set_virtual(true);$simple_id=$simple->save();$ids[]=$simple_id;
    $external=new WC_Product_External();$external->set_name('Native external QA');$external->set_status('publish');$external->set_regular_price('31');$external->set_product_url('https://example.invalid/native-product');$external->set_button_text('External native CTA');$external_id=$external->save();$ids[]=$external_id;
    $grouped=new WC_Product_Grouped();$grouped->set_name('Native grouped QA');$grouped->set_status('publish');$grouped->set_children([$simple_id]);$grouped_id=$grouped->save();$ids[]=$grouped_id;
    $post=$make('Native post QA','FIRST_PAGE<!--nextpage-->SECOND_PAGE','post');$comment=wp_insert_comment(['comment_post_ID'=>$post,'comment_author'=>'QA reader','comment_content'=>'NATIVE_COMMENT_OK','comment_approved'=>1]);
    $cat=wp_insert_term('Native posts QA','category')['term_id'];wp_set_post_terms($post,[$cat],'category');$second=$make('Second native post','SECOND_POST','post');wp_set_post_terms($second,[$cat],'category');
    $menus=[];foreach(['primary','footer'] as $location){$menu=wp_create_nav_menu('Native audit '.$location.' '.wp_generate_uuid4());$menus[]=$menu;wp_update_nav_menu_item($menu,0,['menu-item-title'=>'Native '.$location.' menu','menu-item-url'=>get_permalink($account),'menu-item-status'=>'publish']);$locations=get_theme_mod('nav_menu_locations',[]);$locations[$location]=$menu;set_theme_mod('nav_menu_locations',$locations);}
    $variable=wc_get_product(wc_get_product_id_by_sku('slovenka-coco'));$prices=[];foreach(wc_get_products(['limit'=>-1,'status'=>'publish']) as $p)$prices[$p->get_id()]=(float)$p->get_price();
    echo wp_json_encode(['ids'=>$ids,'company'=>$company,'cart'=>get_permalink($cart),'checkout'=>get_permalink($checkout),'account'=>get_permalink($account),'blockCart'=>get_permalink($blockcart),'blockCheckout'=>get_permalink($blockcheckout),'alias'=>$alias,'simple'=>$simple_id,'external'=>get_permalink($external_id),'grouped'=>get_permalink($grouped_id),'variable'=>$variable->get_permalink(),'post'=>$post,'category'=>$cat,'menus'=>$menus,'comment'=>$comment,'prices'=>$prices,'shop'=>get_post_type_archive_link('product')]);
    '''))
    before = wp('echo wp_json_encode([get_option("woocommerce_currency"),get_option("woocommerce_cart_page_id"),get_option("woocommerce_checkout_page_id"),get_option("page_on_front"),get_post(get_option("woocommerce_cart_page_id"))->post_content,wp_count_posts("product"),wp_count_posts("attachment")]);')
    auth=json.loads(wp('$u=get_users(["role"=>"administrator","number"=>1])[0];wp_set_current_user($u->ID);echo wp_json_encode(["cookie"=>AUTH_COOKIE."=".wp_generate_auth_cookie($u->ID,time()+600,"auth")."; ".LOGGED_IN_COOKIE."=".wp_generate_auth_cookie($u->ID,time()+600,"logged_in"),"nonce"=>wp_create_nonce("slovenka_setup")]);'))
    request=urllib.request.Request(a.url+'/wp-admin/themes.php?page=slovenka-setup',headers={'Cookie':auth['cookie']})
    setup=BeautifulSoup(urllib.request.urlopen(request).read(),'html.parser')
    auth['nonce']=setup.select_one('input[name="_wpnonce"]')['value']
    request=urllib.request.Request(a.url+'/wp-admin/admin-post.php',data=urllib.parse.urlencode({'action':'slovenka_setup','_wpnonce':auth['nonce'],'assign_empty':1}).encode(),headers={'Cookie':auth['cookie']})
    assert urllib.request.urlopen(request).status==200
    after = wp('echo wp_json_encode([get_option("woocommerce_currency"),get_option("woocommerce_cart_page_id"),get_option("woocommerce_checkout_page_id"),get_option("page_on_front"),get_post(get_option("woocommerce_cart_page_id"))->post_content,wp_count_posts("product"),wp_count_posts("attachment")]);')
    assert before == after, 'Setup/admin request changed merchant settings, content or product/media records'
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl): return None
    try:
        urllib.request.build_opener(NoRedirect).open(a.url+'/?'+urllib.parse.urlencode({'page_id':created['alias'],'order-received':123,'key':'wc_order_qa'}))
        raise AssertionError('Legacy alias did not redirect')
    except urllib.error.HTTPError as error:
        assert error.code==302
        target=urllib.parse.parse_qs(urllib.parse.urlsplit(error.headers['Location']).query)
        assert target.get('order-received')==['123'] and target.get('key')==['wc_order_qa'], 'Checkout endpoint/key lost'
    assert wp('echo apply_filters("woocommerce_add_to_cart_redirect",false)===false ? "native":"forced";') == 'native'
    for query in [{'post_type':'product'},{'product_cat':'trousers'},{'s':'Native','post_type':'product'}]:
        d=read(params={**query,'native_audit':1})
        assert ids(d), 'No native products: '+str(query)
        html=str(d)
        for hook in ['woocommerce_before_main_content','woocommerce_shop_loop_header','woocommerce_before_shop_loop','woocommerce_shop_loop','woocommerce_before_shop_loop_item','woocommerce_after_shop_loop','woocommerce_after_main_content']:
            assert 'AUDIT:'+hook in html, hook+' did not execute: '+str(query)
        assert d.title.get_text()=='NATIVE_TITLE_OK'
        assert not d.select('#sidebar, .product-card'), 'Custom catalogue/sidebar still present'
    d=read(params={'product_cat':'trousers'})
    assert d.select_one('h1').get_text(strip=True)=='Hlače', 'Term ID resolved as post/page ID'
    prices=created['prices']
    for ordering,reverse in [('price',False),('price-desc',True)]:
        d=read(params={'post_type':'product','orderby':ordering,'min_price':65,'max_price':190})
        values=[prices[str(i)] for i in ids(d)]
        assert values==sorted(values,reverse=reverse), ('Ordering ignored',values)
        assert values and all(65<=value<=190 for value in values)
    d=read(params={'post_type':'product','min_price':150})
    assert ids(d) and all(prices[str(i)]>=150 for i in ids(d)), 'Native min_price ignored'
    first=read(params={'post_type':'product','audit_per_page':2})
    second=read(params={'post_type':'product','audit_per_page':2,'paged':2})
    assert len(ids(first))==len(ids(second))==2 and not set(ids(first))&set(ids(second))
    assert first.select('.woocommerce-pagination a'), 'Native pagination missing'
    excluded=ids(first)[0]
    assert excluded not in ids(read(params={'post_type':'product','audit_exclude':excluded}))
    company=read(params={'page_id':created['company'],'native_audit':1})
    assert company.select_one('.audit-shortcode') and company.select_one('.audit-block') and company.select_one('.audit-content-filter')
    assert 'Native primary menu' in company.header.get_text() and 'Native footer menu' in company.footer.get_text()
    assert company.title.get_text()=='NATIVE_TITLE_OK'
    assert 'SlovenkaSite' not in str(company), 'Full catalogue still serialized into frontend'
    assert company.select('script#slovenka-theme-js') and company.select('link#slovenka-css')
    external=read(created['external'])
    assert external.select_one('form.cart')['action']=='https://example.invalid/native-product'
    assert external.select_one('button.single_add_to_cart_button').get_text(strip=True)=='External native CTA'
    assert read(created['grouped']).select('form.grouped_form input.qty')
    single=read(created['variable'],{'native_audit':1,'audit_rtl':1})
    assert single.select('form.variations_form') and single.html.get('dir')=='rtl' and single.html.get('lang')=='ar'
    for hook in ['woocommerce_before_main_content','woocommerce_before_single_product','woocommerce_after_single_product','woocommerce_after_main_content']:
        assert 'AUDIT:'+hook in str(single)
    post=read(params={'p':created['post']})
    assert post.select('.page-links a') and 'NATIVE_COMMENT_OK' in post.get_text() and post.select('#commentform')
    assert 'SECOND_PAGE' in read(params={'p':created['post'],'page':2}).get_text()
    archive=read(params={'cat':created['category']})
    assert archive.select('.pagination a'), 'WP post archive pagination missing'
    assert read(created['account']).select('form.woocommerce-form-login')
    for key,cls in [('blockCart','wp-block-woocommerce-cart'),('blockCheckout','wp-block-woocommerce-checkout')]:
        assert read(created[key]).select('.'+cls), key+' block did not render'
    print('PASS: native category/search/shop queries, price/sort/pagination, plugin loop/query hooks, WP shortcodes/dynamic blocks/content filters, menus/title/language, merchant settings, simple/external/grouped/variable products, account, cart/checkout blocks, post pagination/comments.')
    # Browser flow can consume the fixture while this runner is alive via an optional callback.
    fixture=Path('/tmp/slovenka-native-browser-fixture.json');fixture.write_text(json.dumps(created))
    browser=Path(__file__).with_name('check-theme-storefront.cjs')
    subprocess.run(['node',str(browser),str(fixture)],check=True)
finally:
    # Pass data through JSON stdin via a temp eval file, avoiding shell interpolation.
    payload={'snapshot':snapshot,'created':created}
    restore=root/'wp-content/native-audit-restore.json';restore.write_text(json.dumps(payload))
    try:
        wp('''$r=json_decode(file_get_contents(ABSPATH.'wp-content/native-audit-restore.json'),true);foreach($r['snapshot']['options'] as $k=>$v)update_option($k,$v);set_theme_mod('nav_menu_locations',$r['snapshot']['menus']);$company=get_option('slovenka_pages')['company.html'];wp_update_post(wp_slash(['ID'=>$company,'post_content'=>$r['snapshot']['company']]));if($r['created']){foreach(array_reverse($r['created']['ids']) as $id)wp_delete_post($id,true);foreach($r['created']['menus'] as $id)wp_delete_nav_menu($id);wp_delete_term($r['created']['category'],'category');wp_delete_comment($r['created']['comment'],true);}''')
    finally:
        restore.unlink(missing_ok=True);probe.unlink(missing_ok=True)
