#!/usr/bin/env node
// Called by check-seo-language.py in each permalink mode.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require('playwright');
const f = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
assert(['localhost','127.0.0.1'].includes(new URL(f.home).hostname));
(async()=>{
  const browser=await chromium.launch();
  try {
    const page=await browser.newPage({viewport:{width:1280,height:1000}});
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.goto(f.home+'?add-to-cart='+f.simple);
    for(const key of ['cart','checkout','blockCart','blockCheckout']) {
      await page.goto(f[key]);
      if(key==='checkout') {
        assert(await page.evaluate(()=>wc_checkout_params.wc_ajax_url.includes('%%endpoint%%')));
        await page.waitForFunction(()=>!document.querySelector('.blockUI'));
        assert.match(await page.locator('#payment').innerText(),/Trenutno nema|Naruči/);
        assert.match(await page.locator('.woocommerce-privacy-policy-text').innerText(),/Vaši lični podaci/);
        await page.locator('#place_order').click();
        await page.locator('.woocommerce-error').waitFor();
        assert.match(await page.locator('.woocommerce-error').innerText(),/obavezno/);
      }
      if(key==='blockCart') {
        await page.locator('.wc-block-cart-item__product a.wc-block-components-product-name').waitFor();
        assert.match(await page.locator('main').innerText(),/Procijenjeni ukupni iznos/);
        assert.match(await page.locator('main').innerText(),/Nastavi na plaćanje/);
      }
      if(key==='blockCheckout') {
        await page.locator('#email').waitFor();
        const text=await page.locator('main').innerText();
        for(const label of ['Kontakt podaci','Adresa za naplatu','Način plaćanja','Pregled narudžbe','Naruči']) assert(text.includes(label),label+' untranslated');
        assert.equal(await page.evaluate(()=>wp.i18n._n('Total price for <quantity/> <productName/> item: <price/>','Total price for <quantity/> <productName/> items: <price/>',2,'woocommerce')), 'Ukupna cijena za <quantity/> komada proizvoda <productName/>: <price/>');
      }
      assert.match(await page.locator('html').getAttribute('lang'),/^bs/);
      assert.match(await page.locator('meta[name=robots]').getAttribute('content'),/noindex/);
      for(const width of [1280,390]) {
        await page.setViewportSize({width,height:1000});
        assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),key+' overflow');
      }
      await page.locator('a.language[hreflang=en]').click();
      assert.match(await page.locator('html').getAttribute('lang'),/^en/);
      assert(new URL(page.url()).pathname.includes('/en/'));
      assert(!new URL(page.url()).searchParams.has('lang'));
      if(key==='blockCheckout') {await page.locator('#email').waitFor();assert.match(await page.locator('main').innerText(),/Contact information/);}
    }
    await page.goto(f.checkout+(f.checkout.includes('?')?'&':'?')+'audit_policy_custom=1');
    assert.match(await page.locator('.woocommerce-privacy-policy-text').innerText(),/Merchant privacy copy remains untouched/);
    await page.goto(f.checkout);
    await page.waitForFunction(()=>!document.querySelector('.blockUI'));
    await page.locator('a.language[hreflang=en]').click();
    await page.waitForFunction(()=>!document.querySelector('.blockUI'));
    await page.locator('#place_order').click();await page.locator('.woocommerce-error').waitFor();
    assert.match(await page.locator('.woocommerce-error').innerText(),/required field/);
    assert.deepEqual(errors,[]);
    console.log('PASS: PHP/JS checkout translations, plurals, English switch, AJAX validation, noindex and mobile layout.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
