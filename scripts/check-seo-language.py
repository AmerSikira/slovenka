#!/usr/bin/env python3
"""Verify language routing and SEO on an isolated WP/Woo store with starter pages.
Creates disposable commerce fixtures and restores options in finally.
"""
import argparse, json, subprocess, urllib.request, urllib.parse, xml.etree.ElementTree as ET
from pathlib import Path
from bs4 import BeautifulSoup
p = argparse.ArgumentParser()
p.add_argument('--wp', required=True)
p.add_argument('--path', required=True)
p.add_argument('--url', default='http://127.0.0.1:5196/')
a = p.parse_args()
assert urllib.parse.urlsplit(a.url).hostname in ['127.0.0.1','localhost']
cmd = ['php','-d','error_reporting=22527','-d','memory_limit=512M',a.wp,'--path='+a.path]
def wp(code): return subprocess.check_output(cmd+['eval', code], text=True).strip()
def read(url):
    with urllib.request.urlopen(url, timeout=30) as r:
        return BeautifulSoup(r.read(),'html.parser'), r.url
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None
probe = Path(a.path)/'wp-content/mu-plugins/slovenka-seo-audit.php'
assert not probe.exists()
probe.write_text('''<?php
add_filter('loop_shop_per_page', fn($count)=>isset($_GET['audit_pagination']) ? 2 : $count);
add_filter('option_woocommerce_checkout_privacy_policy_text', fn($text)=>isset($_GET['audit_policy_custom']) ? 'Merchant privacy copy remains untouched.' : $text);
add_filter('slovenka_seo_enabled', fn($enabled)=>isset($_GET['audit_seo_plugin']) ? false : $enabled);
add_action('wp_head', function(){if(isset($_GET['audit_seo_plugin']))echo '<meta name="description" content="SEO_PLUGIN_OWNS_METADATA">';});
add_action('wp_footer', function(){echo '<span id="audit-methods">'.esc_html(apply_filters('woocommerce_gateway_title','Cash on delivery')).' / '.esc_html(apply_filters('woocommerce_shipping_rate_label','Free shipping')).' / '.esc_html(apply_filters('woocommerce_gateway_title','Custom merchant method')).'</span>';});
''')
snapshot = json.loads(wp('echo wp_json_encode(["permalink_structure"=>get_option("permalink_structure"),"woocommerce_cart_page_id"=>get_option("woocommerce_cart_page_id"),"woocommerce_checkout_page_id"=>get_option("woocommerce_checkout_page_id"),"blog_public"=>get_option("blog_public"),"woocommerce_checkout_privacy_policy_text"=>get_option("woocommerce_checkout_privacy_policy_text")]);'))
f = None
try:
    f = json.loads(wp('''$p=new WC_Product_Simple();$p->set_name('Language SEO QA');$p->set_description('Provjereni opis proizvoda.');$p->set_short_description('Pamučna odjeća za svakodnevne kombinacije.');$p->set_regular_price('45');$p->set_status('publish');$p->save();$ids=[];
foreach(['cart'=>'[woocommerce_cart]','checkout'=>'[woocommerce_checkout]','blockCart'=>(new ReflectionMethod('WC_Install','get_cart_block_content'))->invoke(null),'blockCheckout'=>(new ReflectionMethod('WC_Install','get_checkout_block_content'))->invoke(null)] as $key=>$content){$ids[$key]=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$key,'post_content'=>$content]);update_post_meta($ids[$key],'_slovenka_source',str_contains(strtolower($key),'cart')?'cart.html':'checkout.html');}
update_option('woocommerce_cart_page_id',$ids['cart']);update_option('woocommerce_checkout_page_id',$ids['checkout']);update_option('blog_public',1);update_option('woocommerce_checkout_privacy_policy_text','Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].');echo wp_json_encode(['product'=>$p->get_id(),'pages'=>$ids]);'''))
    for structure in ['', '/%postname%/']:
        wp('update_option("permalink_structure",'+json.dumps(structure)+');')
        wp('flush_rewrite_rules(false);')
        urls = json.loads(wp('''$f=json_decode('''+json.dumps(json.dumps(f))+''',true);$urls=['shop'=>get_post_type_archive_link('product'),'product'=>get_permalink($f['product']),'home'=>home_url('/'),'account'=>wc_get_page_permalink('myaccount'),'currency'=>get_woocommerce_currency()];foreach($f['pages'] as $k=>$id)$urls[$k]=get_permalink($id);echo wp_json_encode($urls);'''))
        # CLI/admin locales stay merchant-controlled; force the default-language URLs.
        urls = {k: (v.replace('/en/', '/') if k!='currency' else v) for k,v in urls.items()}
        product, product_url = read(urls['product'])
        assert product.html['lang'].startswith('bs')
        assert product.select_one('#audit-methods').get_text()=='Plaćanje pouzećem / Besplatna dostava / Custom merchant method'
        assert len(product.select('link[rel=canonical]')) == 1
        assert product.select_one('meta[name=description]')['content'].startswith('Pamučna')
        assert product.select_one('meta[property="og:url"]')['content'] == product.select_one('link[rel=canonical]')['href']
        assert product.select_one('meta[name="twitter:card"]')
        graphs = []
        for script in product.select('script[type="application/ld+json"]'):
            data=json.loads(script.string)
            graphs.extend(data.get('@graph',[data]))
        assert sum(n.get('@type')=='Organization' for n in graphs)==1
        assert sum(n.get('@type')=='WebSite' for n in graphs)==1
        schema_product=next(n for n in graphs if n.get('@type')=='Product')
        assert schema_product['name']=='Language SEO QA'
        offer=schema_product['offers'][0]
        assert offer['priceCurrency']==urls['currency'] and float(offer['price'])==45
        assert offer['availability'].endswith('/InStock')
        assert 'aggregateRating' not in schema_product
        tracking=urls['product']+('&' if '?' in urls['product'] else '?')+'utm_source=qa&attribute_size=38'
        tracked,_=read(tracking)
        assert tracked.select_one('link[rel=canonical]')['href']==product.select_one('link[rel=canonical]')['href']
        assert 'noindex' in tracked.select_one('meta[name=robots]')['content']
        english_url=product.select_one('a.language[hreflang=en]')['href']
        english,_=read(english_url)
        assert '/en/' in urllib.parse.urlsplit(english_url).path and 'lang=' not in english_url
        assert english.html['lang'].startswith('en')
        assert english.select_one('#audit-methods').get_text()=='Cash on delivery / Free shipping / Custom merchant method'
        assert 'noindex' in english.select_one('meta[name=robots]')['content'], 'Untranslated merchant product copy must not be indexed as English'
        assert '/en/' in english.select_one('form.search-form')['action']
        assert not english.select('form.search-form input[name=lang]')
        assert all('lang=' not in link['href'] for link in english.select('a[href]'))
        old=urls['product']+('&' if '?' in urls['product'] else '?')+'lang=en&attribute_size=38'
        try: urllib.request.build_opener(NoRedirect).open(old)
        except urllib.error.HTTPError as r:
            assert r.code==301 and '/en/' in r.headers['Location'] and 'attribute_size=38' in r.headers['Location'] and 'lang=' not in r.headers['Location']
        else: raise AssertionError('Legacy language URL did not redirect')
        home,_=read(urls['home'])
        assert len(home.select('h1'))==1
        alternates={link['hreflang']:link['href'] for link in home.select('link[hreflang]')}
        assert set(alternates)=={'bs','en','x-default'}
        en_home,_=read(alternates['en'])
        reciprocal={link['hreflang']:link['href'] for link in en_home.select('link[hreflang]')}
        assert reciprocal==alternates and en_home.html['lang'].startswith('en')
        no_theme,_=read(urls['product']+('&' if '?' in urls['product'] else '?')+'audit_seo_plugin=1')
        assert len(no_theme.select('meta[name=description]'))==1 and no_theme.select_one('meta[name=description]')['content']=='SEO_PLUGIN_OWNS_METADATA'
        assert not no_theme.select('script.slovenka-schema')
        page2=urls['shop']+('&' if '?' in urls['shop'] else '?')+'paged=2&audit_pagination=1'
        second,_=read(page2)
        page2canonical=second.select_one('link[rel=canonical]')['href']
        assert ('/page/2/' in page2canonical or 'paged=2' in page2canonical) and 'audit_pagination' not in page2canonical, 'Wrong pagination canonical: '+page2canonical
        if structure:
            sitemap=ET.fromstring(urllib.request.urlopen(urls['home']+'wp-sitemap-posts-page-1.xml').read())
            locations=[n.text for n in sitemap.findall('.//{*}loc')]
            assert all(urls[key] not in locations for key in ['cart','checkout','blockCart','blockCheckout']), 'Private commerce pages leaked into sitemap'
            try: read(urls['home']+'en/slovenka-seo-does-not-exist-qa/')
            except urllib.error.HTTPError as r: assert r.code==404
            else: raise AssertionError('Missing English route must return 404')
        fixture=Path(a.path)/'wp-content/slovenka-seo-fixture.json'
        fixture.write_text(json.dumps({**urls,'simple':f['product']}))
        subprocess.run(['node',str(Path(__file__).with_suffix('.cjs')),str(fixture)],check=True)
        print('PASS: '+('pretty' if structure else 'plain')+' URLs, redirects, reciprocal hreflang, canonical/social metadata, live Product schema, noindex, SEO plugin handoff.')
finally:
    restore=Path(a.path)/'wp-content/slovenka-seo-restore.json'
    restore.write_text(json.dumps({'snapshot':snapshot,'fixture':f}))
    try:
        wp('''$r=json_decode(file_get_contents(ABSPATH.'wp-content/slovenka-seo-restore.json'),true);foreach($r['snapshot'] as $k=>$v)update_option($k,$v);if($r['fixture']){foreach($r['fixture']['pages'] as $id)wp_delete_post($id,true);wp_delete_post($r['fixture']['product'],true);}''')
        wp('flush_rewrite_rules(false);')
    finally:
        restore.unlink(missing_ok=True);probe.unlink(missing_ok=True)
        (Path(a.path)/'wp-content/slovenka-seo-fixture.json').unlink(missing_ok=True)
