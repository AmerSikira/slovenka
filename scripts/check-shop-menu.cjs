#!/usr/bin/env node
const assert = require('node:assert/strict');
const {chromium} = require('playwright');
const url = process.argv[2] || 'http://127.0.0.1:5196/';
assert(['localhost','127.0.0.1'].includes(new URL(url).hostname));
(async () => {
  const browser = await chromium.launch({headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1440,height:900}});
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(url);
    const item = page.locator('.shop-menu-item').first();
    const link = item.locator(':scope > a');
    const toggle = item.locator(':scope > button');
    const submenu = item.locator(':scope > .sub-menu');
    assert.equal(await page.locator('.native-categories').count(), 0);
    assert(await submenu.locator('a').count());
    assert.equal(await submenu.isVisible(), false);
    const target = await link.getAttribute('href');
    assert.equal(new URL(target).searchParams.get('lang'), new URL(url).searchParams.get('lang') || 'bs');
    await link.hover();
    assert(await submenu.isVisible());
    assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
    await page.keyboard.press('Escape');
    assert.equal(await submenu.isVisible(), false);
    await page.mouse.move(0, 400);
    assert.equal(await submenu.isVisible(), false, 'Dismissed submenu should stay closed when the pointer leaves');
    await page.locator('.wordmark, .custom-logo-link').first().focus();
    await link.focus();
    assert(await submenu.isVisible());
    await page.keyboard.press('Tab');
    assert(await toggle.evaluate(element => element === document.activeElement));
    await page.keyboard.press('Tab');
    assert(await submenu.locator('a').first().evaluate(element => element === document.activeElement));
    for (const href of await submenu.locator('a').evaluateAll(links => links.map(link => link.href))) {
      assert.equal(new URL(href).searchParams.get('lang'), new URL(url).searchParams.get('lang') || 'bs');
    }
    await page.setViewportSize({width:390,height:844});
    await page.reload();
    await page.locator('.mobile-menu').click();
    assert.equal(await submenu.isVisible(), false);
    await toggle.click();
    assert(await submenu.isVisible());
    assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
    await toggle.click();
    assert.equal(await submenu.isVisible(), false);
    await toggle.click();
    await page.keyboard.press('Escape');
    assert.equal(await submenu.isVisible(), false);
    assert(await page.locator('#site-navigation').isVisible(), 'First Escape should close only the category submenu');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('#site-navigation').isVisible(), false);
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.locator('.mobile-menu').click();
    await Promise.all([page.waitForNavigation(), link.click()]);
    assert(await page.locator('body.woocommerce-shop').count(), 'Shop link should open the shop archive');
    assert.deepEqual(errors, []);
    console.log('PASS: Shop hover, keyboard, Escape, mobile disclosure, category language, shop link, no overflow or JavaScript errors.');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode=1; });
