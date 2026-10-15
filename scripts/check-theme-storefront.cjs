#!/usr/bin/env node
// Requires Playwright. Run only against a disposable localhost WordPress store.
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const shop = process.argv[2] || 'http://127.0.0.1:5191/?post_type=product';
assert(['localhost', '127.0.0.1'].includes(new URL(shop).hostname));
(async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(15000);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(shop);
    const site = await page.evaluate(() => window.SlovenkaSite);
    // Check every imported template, including cart, checkout and both languages.
    for (const url of new Set(Object.values(site.urls))) {
      const response = await page.request.get(url);
      assert(response.ok(), `Broken page: ${url}`);
      assert(!/id=["']sidebar["']|class=["'][^"']*widget-area/.test(await response.text()), `Sidebar on ${url}`);
    }
    // Both the WooCommerce archive and the English ordinary page must use the
    // same complete catalogue markup, without the native duplicate loop.
    for (const url of [shop, site.urls['shop-en.html']]) {
      await page.goto(url);
      assert.equal(await page.locator('h1').count(), 1, 'Repeated shop intro');
      assert.equal(await page.locator('#sidebar, .woocommerce-products-header, ul.products').count(), 0);
      assert.equal(await page.locator('.sort-label select').count(), 1, 'Shop controls stripped');
      assert.equal(await page.locator('.product-card').count(), site.products.length);
      for (const link of await page.locator('.product-card a[href]').evaluateAll(nodes => nodes.map(node => node.href))) {
        assert(!new URL(link).pathname.endsWith('.html'), `Static product link: ${link}`);
      }
      // Request all lazy photos without relying on viewport preload heuristics.
      await page.locator('.product-card img').evaluateAll(images => images.forEach(image => image.loading = 'eager'));
      await page.waitForFunction(() => [...document.querySelectorAll('.product-card img')].every(image => image.complete && image.naturalWidth > 0));
    }
    await page.goto(shop);
    await page.locator('.desktop-filters select[data-filter="color"]').selectOption('green');
    await page.waitForURL(url => url.searchParams.get('color') === 'green');
    assert.equal(await page.locator('.product-card').count(), site.products.filter(p => p.colors.some(c => c.id === 'green')).length);
    await page.locator('.sort-label select').selectOption('price-asc');
    await page.waitForURL(url => url.searchParams.get('sort') === 'price-asc');
    const model = site.products.find(product => product.name.startsWith('COCO'));
    assert(model, 'Import the starter catalogue before running this flow');
    const coco = page.locator(`.product-card[data-product="${model.id}"] a`).first();
    await coco.click();
    assert(new URL(page.url()).searchParams.get('attribute_colour') === 'green');
    assert.equal(await page.locator('form.variations_form').count(), 1);
    assert.equal(await page.locator('select[name="attribute_colour"]').inputValue(), 'green');
    assert.equal(await page.locator('#sidebar').count(), 0);
    const size = await page.locator('select[name="attribute_size"] option:not([value=""]):not([disabled])').first().getAttribute('value');
    await page.locator('select[name="attribute_size"]').selectOption(size);
    await page.locator('.single_add_to_cart_button').click();
    await page.waitForURL(url => url.href === site.cart);
    assert((await page.locator('main').innerText()).includes('COCO'), 'Chosen product missing from native cart');
    await page.goto(site.urls['checkout.html']);
    assert.equal(await page.locator('#sidebar').count(), 0);
    // Exercise WooCommerce's native product fallback route too.
    await page.goto(model.permalink);
    await page.waitForFunction(() => getComputedStyle(document.querySelector('.woocommerce-product-gallery')).opacity === '1');
    assert.equal(await page.locator('#sidebar').count(), 0);
    assert.equal(await page.locator('h1').count(), 1);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(shop);
    assert.equal(await page.locator('h1').count(), 1);
    assert(await page.locator('.filter-trigger').isVisible());
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    assert.deepEqual(errors, [], 'Storefront JavaScript errors');
    console.log('PASS: all imported routes have no sidebar; both shop languages have one intro/grid, working images, filters, product URLs, native variant selection, native cart/checkout and mobile layout.');
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
