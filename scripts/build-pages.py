#!/usr/bin/env python3
"""Materialize standalone pages. Optional authoring tool: Python + BeautifulSoup + Node."""
from pathlib import Path
from copy import deepcopy
from urllib.parse import urlsplit
import json, subprocess, re
from html import escape
from bs4 import BeautifulSoup
ROOT = Path(__file__).resolve().parents[1]
source = BeautifulSoup((ROOT / 'templates/site-source.html').read_text(), 'html.parser')
# Keep browser data and generated catalogue markup in sync without a network fetch.
prefix = (ROOT / 'script.js').read_text().split('// Small helpers.')[0]
data = json.loads(subprocess.check_output(['node', '-e', prefix + '\nprocess.stdout.write(JSON.stringify({PRODUCTS,IMAGES}));'], text=True))
products, images = data['PRODUCTS'], data['IMAGES']

def fragment(markup): return BeautifulSoup(markup, 'html.parser')
def replace_contents(node, markup):
    node.clear()
    for child in list(fragment(markup).contents): node.append(child)
def url(path, lang):
    parts = urlsplit(path)
    filename = 'index' if parts.path == '/' else parts.path.strip('/').replace('/', '-')
    return filename + ('-en' if lang == 'en' else '') + '.html' + ('?' + parts.query if parts.query else '') + ('#' + parts.fragment if parts.fragment else '')
def money(price, lang): return (f'{price:.2f}'.replace('.', ',') if lang == 'bs' else f'{price:.2f}') + ' KM'
def picture(path, alt='', eager=False):
    m = images[path]
    priority = ' fetchpriority="high"' if eager else ''
    return f'<picture class="responsive-image"><source type="image/webp" srcset="{m["srcSet"]}" sizes="(max-width:767px) 100vw, 50vw"><img src="{m["fallback"]}" width="{m["width"]}" height="{m["height"]}" alt="{escape(alt)}" loading="{"eager" if eager else "lazy"}"{priority}></picture>'
def gallery_path(p, n): return f'{p["name"]}/{"Sređeno" if p["id"] == "aria" else "Sređene"}/{p["name"]} {n}.png'
def swatches(p, lang, context='card'):
    return ''.join(f'<button type="button" class="swatch {"selected" if i == 0 else ""}" style="--swatch:{c["hex"]}" data-action="{context}-color" data-product="{p["id"]}" data-color="{c["id"]}" aria-label="{c["name"][lang]}" aria-pressed="{str(i == 0).lower()}"></button>' for i,c in enumerate(p['colors']))
def card(p, lang):
    c = p['colors'][0]; href = url('/product/' + p['id'] + '?color=' + c['id'], lang)
    secondary = picture(gallery_path(p,c['images'][1])) if len(c['images']) > 1 else ''
    secondary = secondary.replace('<img ', '<img class="hover-image" ')
    return f'<article class="product-card" data-product="{p["id"]}"><a class="product-image" href="{href}">{picture(c["cover"], p["name"] + " — " + p["label"][lang])}{secondary}<span class="product-tag">{p["name"]}</span></a><div class="product-card-top"><a href="{href}"><h3>{p["name"]}</h3></a><span>{money(p["price"],lang)}</span></div><a class="product-description" href="{href}">{p["label"][lang]}</a><div class="swatches">{swatches(p,lang)}<span>{"Primjer cijene" if lang == "bs" else "Sample price"}</span></div></article>'
def body_content(key, lang):
    if key == 'home' and lang == 'bs': return fragment(str(source.select_one('#main'))).select_one('main')
    return fragment(source.find('template', id=key + '-' + lang).decode_contents())

def materialize(key, lang, p=None):
    doc = BeautifulSoup('<!doctype html><html><head></head><body></body></html>', 'html.parser')
    doc.html['lang'] = lang
    doc.head.replace_with(deepcopy(source.head))
    doc.body['data-page'] = '/' if key == 'home' else '/' + key.replace('support-', 'support/').replace('product-', 'product/')
    skip = deepcopy(source.select_one('.skip-link')); skip.string = 'Preskoči na sadržaj' if lang == 'bs' else 'Skip to content'; doc.body.append(skip)
    header = source.find('template', id='header-' + lang)
    doc.body.append(fragment(header.decode_contents()))
    toggle = doc.select_one('header .nav-button')
    if toggle:
        shop_link = doc.new_tag('a', href=url('/shop',lang));shop_link.string='Trgovina' if lang=='bs' else 'Shop'
        toggle.insert_before(shop_link)
        chevron = deepcopy(toggle.select_one('svg'));toggle.clear();toggle.append(chevron)
        toggle['aria-label']='Kategorije trgovine' if lang=='bs' else 'Shop categories'
    fallback = doc.new_tag('noscript')
    fallback.append(fragment('<nav class="container" aria-label="'+('Navigacija' if lang=='bs' else 'Navigation')+'">'+ ' · '.join(f'<a href="{url(route,lang)}">{label}</a>' for route,label in [('/shop','Trgovina' if lang=='bs' else 'Shop'),('/company','O nama' if lang=='bs' else 'Our company'),('/manufacturing','Proizvodnja' if lang=='bs' else 'Manufacturing'),('/contact','Kontakt' if lang=='bs' else 'Contact'),('/cart','Korpa' if lang=='bs' else 'Cart')]) + '</nav>'))
    doc.body.append(fallback)
    main = doc.new_tag('main', id='main')
    commerce_path = ROOT / 'templates/commerce' / f'{key}-{lang}.html'
    if key in ['cart','checkout','order'] and commerce_path.exists(): content = fragment(commerce_path.read_text())
    elif key == 'cart' or key == 'order': return
    else: content = body_content('product-aria' if p else key, lang)
    if content.select_one('main'):
        for child in list(content.select_one('main').contents): main.append(child)
    else:
        for child in list(content.contents): main.append(child)
    doc.body.append(main)
    if key == 'home':
        contact = body_content('contact', lang).select_one('.contact-section')
        contact.select_one('.contact-breadcrumbs').decompose()
        contact.select_one('h1').name = 'h2'
        contact.select_one('#contact-form-title').name = 'h3'
        main.append(contact)
    doc.body.append(fragment(source.find('template', id='footer-' + lang).decode_contents()))
    doc.body.append(deepcopy(source.select_one('#site-dialog')))
    for template_id in ['shopMenu-' + lang, 'search-' + lang, 'filters-' + lang, 'size-guide']:
        doc.body.append(deepcopy(source.find('template', id=template_id)))
    # Complete base catalogue markup, readable before JavaScript runs.
    for existing in list(main.select('.product-card')):
        link = existing.select_one('a[href]'); match = re.search(r'(?:product/|product-)([^?#.]+)', link['href']) if link else None
        prod = next((x for x in products if match and x['id'] == match[1]), None)
        if prod: existing.replace_with(fragment(card(prod,lang)))
    if key == 'shop':
        replace_contents(main.select_one('.listing-grid'), ''.join(card(x,lang) for x in products))
        for i,s in enumerate(main.select('.desktop-filters select')): s['data-filter'] = ['color','size','price'][i]
        main.select_one('.sort-label select')['data-filter'] = 'sort'
        main.select_one('.filter-trigger')['data-action'] = 'filters'
    if p:
        c=p['colors'][0]; main.h1.string = p['name'];main.select_one('.breadcrumbs [aria-current="page"]').string=p['name']
        main.select_one('.product-subtitle').string=p['label'][lang]
        replace_contents(main.select_one('.product-price'), money(p['price'],lang)+f'<span>{"Primjer cijene" if lang == "bs" else "Sample price"}</span>')
        main.select_one('.product-intro').string=p['description'][lang]
        main.select_one('.product-accordions details p').string=p['description'][lang]
        replace_contents(main.select_one('.swatches.large'),swatches(p,lang,'product'))
        replace_contents(main.select_one('.sizes'), ''.join(f'<button type="button" data-action="size" data-size="{size}" aria-pressed="false" aria-label="{size}{(" — nedostupno" if lang == "bs" else " — unavailable") if size in p["unavailable"] else ""}"{" disabled" if size in p["unavailable"] else ""}>{size}</button>' for size in p['sizes']))
        main.select_one('.size-heading button')['data-action']='size-guide'
        main.select_one('.add-to-bag')['data-action']='add-to-bag'
        paths=[gallery_path(p,n) for n in c['images']]
        if p['id']=='hasi': paths.append('HASI/Primjena/HASI 1.png')
        replace_contents(main.select_one('.gallery-main'), picture(paths[0],p['name'] + ', ' + c['name'][lang],True) + f'<span class="gallery-count">1 / {len(paths)}</span>')
        replace_contents(main.select_one('.gallery-thumbnails'), ''.join(f'<button type="button" data-action="gallery" data-index="{i}" class="{"selected" if i == 0 else ""}" aria-label="{"Prikaz" if lang == "bs" else "View"} {i+1}" aria-pressed="{str(i==0).lower()}">{picture(path)}</button>' for i,path in enumerate(paths)))
        replace_contents(main.select_one('.variant-group p'), f'{"Boja" if lang == "bs" else "Colour"}: <strong>{c["name"][lang]}</strong>')
        replace_contents(main.select_one('.related-products .product-grid'), ''.join(card(x,lang) for x in products if x['id'] != p['id']))
    for count in doc.select('.bag-count'): count.string='0'
    for trigger in doc.select('.bag-trigger'): trigger['aria-label']='Korpa (0)' if lang=='bs' else 'Bag (0)'
    toggle=doc.select_one('header .nav-button')
    if toggle: toggle.attrs.pop('aria-controls',None)
    path=doc.body['data-page']
    for a in doc.select('a[href]'):
        if a['href'].startswith('#/'): a['href']=url(a['href'][1:],lang)
    # Templates are inert documents and need their own native URL conversion.
    for template in doc.select('template'):
        for a in template.select('a[href]'):
            if a['href'].startswith('#/'): a['href']=url(a['href'][1:],lang)
    for button in list(doc.select('button.language')):
        a=doc.new_tag('a', href=url(path,'en' if lang=='bs' else 'bs')); a['class']=['language']; a['hreflang']='en' if lang=='bs' else 'bs'
        a.string='English' if lang=='bs' else 'Bosanski';button.replace_with(a)
    for a in doc.select('.desktop-nav a'):
        if a['href'].split('?')[0] == url(path,lang): a['aria-current']='page';a['class']=a.get('class',[])+['active']
    for form in doc.select('.search-panel form'):
        form['action']=url('/shop',lang);form['method']='get'
        form.select_one('input')['name']='q'
        form.select_one('button')['type']='submit'
    for button in doc.select('button'):
        if not button.has_attr('type'):
            button['type']='submit' if button.find_parent('form') and 'button' in button.get('class',[]) else 'button' 
    title = main.select_one('h1')
    page_title = title.get_text(' ',strip=True) if title else 'MK Slovenka'
    doc.title.string = page_title + ' — MK Slovenka'
    lead = main.select_one('.product-intro, .muted, .editorial-intro p, .lead')
    description = (p['description'][lang] if p else (page_title.rstrip('.!?') + '. ' + (lead.get_text(' ',strip=True) if lead else ('MK Slovenka — konfekcijska proizvodnja od 1979.' if lang=='bs' else 'MK Slovenka — garment manufacturing since 1979.'))))
    doc.select_one('meta[name="description"]')['content']=description
    doc.head.append(doc.new_tag('link', rel='alternate', hreflang='en' if lang=='bs' else 'bs', href=url(path,'en' if lang=='bs' else 'bs')))
    if key in ['cart','checkout','order']:
        doc.head.append(doc.new_tag('link', rel='stylesheet', href='commerce.css'))
        doc.head.append(doc.new_tag('script', src='commerce.js', defer=''))
    filename=url(path,lang)
    (ROOT / filename).write_text(str(doc) + '\n')
    print(filename)

pages=['home','shop','company','manufacturing','inquiry','contact','support-delivery','support-returns','support-sizing','support-privacy','support-terms','cart','checkout','order']
for lang in ['bs','en']:
    for key in pages: materialize(key,lang)
    for p in products: materialize('product-'+p['id'],lang,p)
