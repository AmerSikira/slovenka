#!/usr/bin/env python3
"""Verify storefront language across actual WP/WooCommerce routes."""
import argparse
from urllib.request import urlopen
from urllib.parse import urljoin, urlsplit, parse_qs
from bs4 import BeautifulSoup

parser = argparse.ArgumentParser()
parser.add_argument('--url', default='http://127.0.0.1:5196/')
a = parser.parse_args()
def read(url):
    return BeautifulSoup(urlopen(urljoin(a.url, url), timeout=25).read(), 'html.parser')
def language(doc):
    return doc.html.get('lang', '').split('-')[0]
home = read(a.url)
product_link = next(link['href'] for link in home.select('a[href]') if '/product/' in link['href'] or 'product=' in link['href'])
product = read(product_link)
assert language(home) == language(product), 'Homepage and product languages differ'
assert language(home) == 'bs', 'Fixture needs a Bosnian homepage'
assert 'Dodaj u korpu' in product.get_text(' ', strip=True), 'Bosnian add-to-cart label missing'
assert 'Choose an option' not in product.get_text(), 'English product controls on Bosnian page'
switch = product.select_one('a.language[hreflang="en"]')
assert switch, 'Product language switch missing'
english = read(switch['href'])
assert language(english) == 'en', 'English product switch failed'
assert 'Add to cart' in english.get_text(), 'English controls missing'
assert parse_qs(urlsplit(english.select_one('.bag-trigger')['href']).query).get('lang') == ['en'], 'Cart loses selected language'
cart = read(english.select_one('.bag-trigger')['href'])
assert language(cart) == 'en', 'Cart resets English selection'
assert 'Your cart is currently empty' in cart.get_text() or 'Proceed to checkout' in cart.get_text(), 'English cart translation missing'
bs_cart_link = cart.select_one('a.language[hreflang="bs"]')
assert bs_cart_link, 'Cart language switch missing'
assert language(read(bs_cart_link['href'])) == 'bs', 'Bosnian cart switch failed'
assert english.select_one('form.search-form input[name="lang"][value="en"]'), 'Search loses language'
print('PASS: Bosnian homepage → product, translated Woo controls, English switch → cart, Bosnian cart switch, search language')
