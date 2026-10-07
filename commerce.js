"use strict";

// Static WooCommerce design preview. WordPress/WooCommerce replaces this adapter.
(() => {
  const api = window.SlovenkaCommerce;
  if (!api) return;
  const root = document.querySelector('.commerce-page');
  if (!root) return;
  const { PRODUCTS, money, t, image, lang } = api;
  const q = (selector) => root.querySelector(selector);
  const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const page = (name) => `${name}${lang === 'en' ? '-en' : ''}.html`;
  const storageKey = 'slovenka-preview-orders';
  let method = api.read('slovenka-preview-delivery', 'flat_rate');
  if (!['flat_rate', 'local_pickup'].includes(method)) method = 'flat_rate';
  let submitting = false;
  const rows = () => api.getBag();
  const subtotal = () => rows().reduce((sum, row) => sum + PRODUCTS.find((p) => p.id === row.id).price * row.qty, 0);
  const delivery = () => method === 'local_pickup' ? 0 : 10;
  const methodLabel = (selected = method) => selected === 'local_pickup' ? t('Lično preuzimanje', "Local pickup") : t('Dostava na adresu', "Delivery to your address");
  const paymentLabel = (payment) => payment === 'bacs' ? t('Direktna bankovna uplata', "Direct bank transfer") : t('Plaćanje pouzećem', "Cash on delivery");
  function status(message) { const el = q('[data-commerce-status]'); if (el) el.textContent = message; }
  function totals(itemsTotal = subtotal(), shipping = delivery(), selectedMethod = method) {
    return `<dl class="commerce-totals"><div><dt>${t('Međuzbir', "Subtotal")}</dt><dd>${money(itemsTotal)}</dd></div><div><dt>${t('Dostava', "Shipping")}<small>${methodLabel(selectedMethod)}</small></dt><dd>${money(shipping)}</dd></div><div><dt>${t('Porezi', "Taxes")}</dt><dd class="commerce-tax-note">${t('Nisu obračunati u pregledu', "Not calculated in preview")}</dd></div><div class="commerce-total"><dt>${t('Ukupno za pregled', "Preview total")}</dt><dd>${money(itemsTotal + shipping)}</dd></div></dl>`;
  }
  function itemDetails(row) {
    const product = PRODUCTS.find((p) => p.id === row.id);
    const color = product.colors.find((c) => c.id === row.color);
    return { product, color };
  }
  function renderCart() {
    const content = q('[data-cart-content]');
    if (!content) return;
    const items = rows();
    content.hidden = !items.length;
    q('[data-commerce-empty]').hidden = !!items.length;
    q('[data-cart-rows]').innerHTML = items.map((row, index) => {
      const { product, color } = itemDetails(row);
      const link = `${page('product-' + product.id)}?color=${encodeURIComponent(color.id)}`;
      return `<tr><td><div class="commerce-cart-product"><a href="${link}" class="commerce-product-photo">${image(color.cover, `${product.name} — ${color.name[lang]}`)}</a><div><a href="${link}"><h3>${product.name}</h3></a><p>${product.label[lang]}</p><dl class="commerce-variation"><div><dt>${t('Boja', "Colour")}</dt><dd>${color.name[lang]}</dd></div><div><dt>${t('Veličina', "Size")}</dt><dd>${escape(row.size)}</dd></div></dl><button type="button" class="commerce-remove" data-commerce-remove="${index}" aria-label="${t('Ukloni', "Remove")} ${product.name}, ${color.name[lang]}, ${escape(row.size)}">${t('Ukloni', "Remove")}</button></div></div></td><td class="commerce-unit-price" data-label="${t('Cijena', "Price")}">${money(product.price)}</td><td data-label="${t('Količina', "Quantity")}"><label class="commerce-quantity"><span class="sr-only">${t('Količina za', "Quantity for")} ${product.name}, ${color.name[lang]}, ${escape(row.size)}</span><input type="number" min="1" max="5" step="1" value="${row.qty}" data-commerce-qty="${index}" inputmode="numeric" aria-describedby="cart-quantity-limit"></label></td><td class="commerce-line-total" data-label="${t('Ukupno', "Subtotal")}">${money(product.price * row.qty)}</td></tr>`;
    }).join('');
    let limit = q('#cart-quantity-limit');
    if (!limit) {
      limit = document.createElement('p');
      limit.id = 'cart-quantity-limit';
      limit.className = 'commerce-note commerce-quantity-note';
      q('.commerce-cart-table').after(limit);
    }
    limit.textContent = t('Najviše 5 komada po odabranoj boji i veličini u pregledu.', "Up to 5 items per selected colour and size in this preview.");
    renderTotals();
  }
  function renderTotals() {
    root.querySelectorAll('[data-commerce-totals]').forEach((el) => { el.innerHTML = totals(); });
    root.querySelectorAll('[name="shipping_method"]').forEach((input) => { input.checked = input.value === method; });
  }
  function renderCheckout() {
    const form = q('[data-commerce-form]');
    if (!form) return;
    const items = rows();
    form.hidden = !items.length;
    q('[data-commerce-empty]').hidden = !!items.length;
    q('[data-checkout-coupon]').hidden = !items.length;
    q('[data-checkout-items]').innerHTML = items.map((row) => {
      const {product, color} = itemDetails(row);
      return `<div class="commerce-summary-item"><a href="${page('product-' + product.id)}?color=${encodeURIComponent(color.id)}">${image(color.cover, product.name)}</a><div><a href="${page('product-' + product.id)}"><strong>${product.name}</strong></a><p>${color.name[lang]} / ${escape(row.size)} / ×${row.qty}</p><span>${money(product.price * row.qty)}</span></div></div>`;
    }).join('');
    renderTotals();
  }
  function readOrders() {
    try { const result = JSON.parse(sessionStorage.getItem(storageKey)); return Array.isArray(result) ? result : []; }
    catch { return []; }
  }
  function addressHTML(address) {
    return `<address>${[`${address.first_name} ${address.last_name}`, address.company, address.address_1, address.address_2, `${address.postcode} ${address.city}`, address.state, t('Bosna i Hercegovina', "Bosnia and Herzegovina")].filter(Boolean).map(escape).join('<br>')}</address>`;
  }
  function renderOrder() {
    const receipt = q('[data-order-receipt]');
    if (!receipt) return;
    const orders = readOrders();
    const id = new URLSearchParams(location.search).get('preview_order');
    const order = id ? orders.find((item) => item.id === id) : orders.at(-1);
    if (!order) return;
    const created = new Date(order.created);
    const months = ['januar','februar','mart','april','maj','juni','juli','august','septembar','oktobar','novembar','decembar'];
    const date = lang === 'bs'
      ? `${created.getDate()}. ${months[created.getMonth()]} ${created.getFullYear()}.`
      : new Intl.DateTimeFormat('en-GB', {dateStyle:'long'}).format(created);
    receipt.innerHTML = `<div class="commerce-confirmation-banner"><span aria-hidden="true">✓</span><div><strong>${t('Probna narudžba je sačuvana.', "Your preview order has been saved.")}</strong><p>${t('Sačuvana je samo u ovoj sesiji preglednika. Nema naplate, slanja e-maila, rezervacije robe ni isporuke.', "Saved only in this browser session. No payment, email, stock reservation or delivery has been made.")}</p></div></div><dl class="commerce-order-overview"><div><dt>${t('Broj probne narudžbe', "Preview order number")}</dt><dd>${escape(order.reference)}</dd></div><div><dt>${t('Datum', "Date")}</dt><dd>${escape(date)}</dd></div><div><dt>${t('E-mail', "Email")}</dt><dd>${escape(order.email)}</dd></div><div><dt>${t('Ukupno', "Total")}</dt><dd>${money(order.total)}</dd></div><div><dt>${t('Način plaćanja', "Payment method")}</dt><dd>${paymentLabel(order.payment)}<small>${t('Samo pregled · nije plaćeno', "Preview only · not paid")}</small></dd></div></dl><div class="commerce-receipt-layout"><section class="commerce-order-details"><h2>${t('Detalji narudžbe', "Order details")}</h2><table class="commerce-order-table"><thead><tr><th scope="col">${t('Proizvod', "Product")}</th><th scope="col">${t('Ukupno', "Total")}</th></tr></thead><tbody>${order.items.map((item) => `<tr><td><a href="${page('product-' + item.id)}"><strong>${escape(item.name)}</strong> × ${item.qty}</a><p>${escape(item.colorLabel[lang])} / ${escape(item.size)}</p></td><td>${money(item.price * item.qty)}</td></tr>`).join('')}</tbody></table>${totals(order.subtotal, order.shipping, order.delivery)}${order.notes ? `<div class="commerce-order-notes"><h3>${t('Napomena', "Order notes")}</h3><p>${escape(order.notes)}</p></div>` : ''}</section><div class="commerce-addresses"><section><h3>${t('Adresa za račun', "Billing address")}</h3>${addressHTML(order.billing)}<p>${escape(order.email)}<br>${escape(order.phone)}</p></section><section><h3>${t('Adresa dostave', "Shipping address")}</h3>${addressHTML(order.shipping_address)}<p>${methodLabel(order.delivery)}</p></section></div></div><a class="button" href="${page('shop')}">${t('Nazad na kolekciju', "Back to the collection")} <span aria-hidden="true">→</span></a>`;
  }
  function setItems(items) {
    const previous = rows().map((row) => ({...row}));
    try { api.setBag(items); return true; }
    catch { try { api.setBag(previous); } catch { /* Restore memory even when persistence is unavailable. */ } status(t('Korpa nije sačuvana. Omogućite pohranu u pregledniku i pokušajte ponovo.', "Your cart could not be saved. Enable browser storage and try again.")); return false; }
  }
  root.addEventListener('click', (event) => {
    const button = event.target.closest('[data-commerce-remove]');
    if (!button) return;
    const index = Number(button.dataset.commerceRemove);
    const name = itemDetails(rows()[index]).product.name;
    if (setItems(rows().filter((_, i) => i !== index))) {
      renderCart();
      status(`${name} ${t('je uklonjen iz korpe.', "has been removed from your cart.")}`);
      const next = q(`[data-commerce-remove="${Math.min(index, rows().length - 1)}"]`) || q('[data-commerce-empty] .button');
      next?.focus();
    }
  });
  root.addEventListener('change', (event) => {
    const input = event.target;
    if (input.matches('[data-commerce-qty]')) {
      const index = Number(input.dataset.commerceQty);
      const previous = rows()[index].qty;
      const value = Number(input.value);
      if (!input.checkValidity() || !Number.isInteger(value)) {
        input.reportValidity(); input.value = previous; return;
      }
      if (setItems(rows().map((row, i) => i === index ? {...row, qty:value} : row))) {
        renderCart();
        q(`[data-commerce-qty="${index}"]`)?.focus();
        status(t('Količina i ukupni iznos su ažurirani.', "Quantity and totals have been updated."));
      }
    }
    if (input.name === 'shipping_method') {
      method = input.value;
      try { api.write('slovenka-preview-delivery', method); } catch { /* Method still works in this page. */ }
      renderTotals();
      status(t('Trošak dostave i ukupni iznos su ažurirani.', "Delivery cost and totals have been updated."));
    }
    if (input.name === 'billing_same_as_shipping') {
      const fieldset = q('[data-billing-address]');
      fieldset.hidden = input.checked;
      fieldset.disabled = input.checked;
    }
  });
  root.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.matches('[data-coupon-form]')) {
      event.preventDefault();
      form.querySelector('.commerce-coupon-message').textContent = t('Kuponi nisu aktivni u ovom pregledu. Popust nije primijenjen.', "Coupons are not active in this preview. No discount has been applied.");
      return;
    }
    if (!form.matches('[data-commerce-form]')) return;
    event.preventDefault();
    if (submitting || !form.reportValidity()) return;
    const items = rows();
    if (!items.length) { renderCheckout(); return; }
    const data = new FormData(form);
    // Native constraints reject missing values; trimming also rejects whitespace-only addresses.
    const requiredNames = ['billing_email','billing_phone','shipping_first_name','shipping_last_name','shipping_address_1','shipping_city','shipping_postcode'];
    if (!data.has('billing_same_as_shipping')) requiredNames.push('billing_first_name','billing_last_name','billing_address_1','billing_city','billing_postcode');
    const blank = requiredNames.find((name) => !String(data.get(name) || '').trim());
    if (blank) { form.elements[blank].focus(); q('[data-checkout-error]').textContent = t('Molimo popunite sva obavezna polja.', "Please fill in all required fields."); return; }
    const address = (prefix) => Object.fromEntries(['first_name','last_name','company','country','address_1','address_2','city','state','postcode'].map((name) => [name, String(data.get(`${prefix}_${name}`) || '').trim()]));
    const id = typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const shipping = address('shipping');
    const order = {id, reference:`PREVIEW-${id.slice(0, 8).toUpperCase()}`, created:new Date().toISOString(), email:String(data.get('billing_email')).trim(), phone:String(data.get('billing_phone')).trim(), billing:data.has('billing_same_as_shipping') ? shipping : address('billing'), shipping_address:shipping, delivery:method, payment:data.get('payment_method'), notes:String(data.get('order_comments') || '').trim(), items:items.map((row) => { const {product,color} = itemDetails(row); return {...row,name:product.name,price:product.price,colorLabel:color.name}; }), subtotal:subtotal(), shipping:delivery(), total:subtotal()+delivery(), status:'preview'};
    const button = form.querySelector('[type="submit"]');
    submitting = true; button.disabled = true;
    try {
      const orders = [...readOrders(), order].slice(-20);
      sessionStorage.setItem(storageKey, JSON.stringify(orders));
      api.setBag([]);
      location.assign(`${page('order')}?preview_order=${encodeURIComponent(id)}`);
    } catch {
      // Restore the cart and remove an uncompleted receipt before allowing a retry.
      try { api.setBag(items); } catch { /* Restore memory when persistence is unavailable. */ }
      try { sessionStorage.setItem(storageKey, JSON.stringify(readOrders().filter((item) => item.id !== id))); } catch { /* Storage may be unavailable. */ }
      submitting = false; button.disabled = false;
      q('[data-checkout-error]').textContent = t('Probna narudžba nije sačuvana. Omogućite pohranu u pregledniku i pokušajte ponovo. Uneseni podaci ostaju u obrascu.', "Your preview order could not be saved. Enable browser storage and try again. Your entered details remain in the form.");
    }
  });
  document.addEventListener('slovenka:bag-updated', () => { renderCart(); renderCheckout(); });
  renderCart(); renderCheckout(); renderOrder();
})();
