#!/usr/bin/env node
// Invoked by check-native-compatibility.py with disposable fixture URLs.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');
const fixture = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
assert(['localhost','127.0.0.1'].includes(new URL(fixture.shop).hostname));
(async () => {
  const browser = await chromium.launch({headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1440,height:1000}});
    page.setDefaultTimeout(20000);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(fixture.shop);
    assert.equal(await page.locator('h1').count(), 1);
    assert(await page.locator('ul.products > li.product').count());
    assert.equal(await page.locator('.product-card, #sidebar').count(), 0);
    await page.locator('select.orderby').selectOption('price-desc');
    await page.waitForURL(url => url.searchParams.get('orderby') === 'price-desc');
    await page.goto(fixture.variable);
    assert(await page.locator('.product-swatch-color').first().evaluate(button => parseFloat(getComputedStyle(button, '::before').height) > 0), 'Colour fill is invisible');
    if (await page.locator('.product-swatch-color[data-value="green"]').getAttribute('aria-pressed') !== 'true') await page.locator('.product-swatch-color[data-value="green"]').click();
    await page.locator('.product-swatch[data-value="36"]').click();
    assert.equal(await page.locator('select[name=attribute_colour]').inputValue(), 'green');
    assert.equal(await page.locator('select[name=attribute_size]').inputValue(), '36');
    assert.equal(await page.locator('.product-swatch[data-value="36"]').getAttribute('aria-pressed'), 'true');
    await page.locator('.product-swatch[data-value="36"]').click();
    await page.locator('.product-swatch-color[data-value="green"]').click();
    assert.equal(await page.locator('.product-swatch[aria-pressed="true"]').count(), 0);
    await page.locator('.product-swatch-color[data-value="green"]').focus();
    await page.keyboard.press('Space');
    await page.locator('.product-swatch[data-value="36"]').click();
    await page.waitForFunction(() => Number(document.querySelector('input.variation_id')?.value)>0 && !document.querySelector('.single_add_to_cart_button').classList.contains('disabled'));
    await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}), page.locator('.single_add_to_cart_button').click()]);
    // The merchant's redirect option is disabled; a successful purchase selection stays here.
    assert.equal(new URL(page.url()).pathname, new URL(fixture.variable).pathname);
    assert(await page.locator('.woocommerce-message, .wc-block-components-notice-banner').count());
    await page.locator('a.bag-trigger').click();
    assert((await page.locator('main').innerText()).includes('COCO'));
    await page.goto(fixture.checkout);
    assert(await page.locator('form.checkout').count());
    await page.goto(fixture.account);
    assert(await page.locator('form.woocommerce-form-login').count());
    await page.goto(fixture.blockCart);
    await page.locator('.wc-block-cart-items').waitFor();
    await page.goto(fixture.blockCheckout);
    await page.locator('.wp-block-woocommerce-checkout').waitFor();
    await page.goto(fixture.shop);
    await page.locator('[data-open-search]').click();
    await page.locator('#site-search input[name=s]').fill('Native');
    await page.locator('#site-search button[type=submit]').click();
    await page.waitForURL(url => url.searchParams.get('s') === 'Native' && url.searchParams.get('post_type') === 'product');
    assert(await page.locator('ul.products > li.product').count());
    for (const width of [1024,390]) {
      await page.setViewportSize({width,height:844});
      await page.goto(fixture.variable);
      assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Product page overflows at '+width);
      assert(await page.locator('.product-swatch-color').first().isVisible());
      assert(await page.locator('.product-swatch[data-value="36"]').isVisible());
      if (width===390) {
        await page.locator('.mobile-menu').click();
        assert(await page.locator('#site-navigation.is-open').isVisible());
      }
      await page.goto(fixture.shop);
      assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Shop overflows at '+width);
    }
    const plain = await browser.newContext({javaScriptEnabled:false});
    const fallback = await plain.newPage();
    await fallback.goto(fixture.variable);
    assert(await fallback.locator('select[name=attribute_colour]').isVisible(), 'No-JS colour select missing');
    assert(await fallback.locator('select[name=attribute_size]').isVisible(), 'No-JS size select missing');
    await plain.close();
    assert.deepEqual(errors, [], 'Browser JavaScript errors');
    console.log('PASS: native ordering, variable add-to-cart honors redirect=no, cart/classic checkout, account, Cart/Checkout blocks, native product search, mobile menu/layout; no JavaScript errors.');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode=1; });
