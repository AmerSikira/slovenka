"use strict";
// Real purchases use WooCommerce's native form handler and server-side validation.
// This capture listener runs before the static site's preview event handlers.
document.addEventListener('click', (event) => {
  const button = event.target.closest('button, a');
  if (!button) return;
  const site = window.SlovenkaSite;
  if (button.matches('.bag-trigger')) {
    event.preventDefault(); event.stopImmediatePropagation();
    if (site.cart) location.href = site.cart;
  }
  if (button.dataset.action !== 'add-to-bag') return;
  event.preventDefault(); event.stopImmediatePropagation();
  const state = typeof productState === 'undefined' ? null : productState;
  const en = document.documentElement.lang === 'en';
  const variant = state && (site.variations[state.p.id] || []).find(v => v.color === state.color.id && v.size === state.size);
  document.querySelector('.field-error')?.remove();
  if (!variant) {
    const message = document.createElement('p');
    message.className = 'field-error'; message.setAttribute('role','alert');
    message.textContent = !site.cart ? (en ? 'Please contact us to order.' : 'Molimo kontaktirajte nas za narudžbu.') : (en ? 'Choose an available colour and size.' : 'Odaberite dostupnu boju i veličinu.');
    button.before(message); return;
  }
  const form = document.createElement('form'); form.method = 'post'; form.action = site.cart;
  for (const [name, value] of Object.entries({'add-to-cart':variant.product, product_id:variant.product, variation_id:variant.id, quantity:1, attribute_colour:variant.color, attribute_size:variant.size})) {
    const input = document.createElement('input'); input.type='hidden'; input.name=name; input.value=value; form.append(input);
  }
  document.body.append(form); form.submit();
}, true);
// Keep size choices aligned with the imported stock and variation prices.
function slovenkaAvailability() {
  if (typeof productState === 'undefined' || !productState) return;
  const variants = window.SlovenkaSite.variations[productState.p.id] || [];
  for (const button of document.querySelectorAll('.sizes button')) {
    button.disabled = !variants.some(v => v.color === productState.color.id && v.size === button.dataset.size);
    if (button.disabled && productState.size === button.dataset.size) {
      productState.size=''; button.classList.remove('selected'); button.setAttribute('aria-pressed','false');
    }
  }
  const variant = variants.find(v => v.color === productState.color.id && v.size === productState.size);
  if (variant) {
    const price = document.querySelector('.product-price');
    price.firstChild.textContent = window.SlovenkaCommerce.money(variant.price);
  }
}
document.addEventListener('click', event => {
  if (event.target.closest('[data-action="product-color"], [data-action="size"]')) slovenkaAvailability();
});
if (typeof productState !== 'undefined' && productState) {
  const sizes = document.querySelector('.sizes');
  sizes.replaceChildren();
  for (const size of productState.p.sizes) {
    const button = document.createElement('button');
    button.type='button'; button.dataset.action='size'; button.dataset.size=size;
    button.textContent=size; button.setAttribute('aria-pressed','false'); sizes.append(button);
  }
}
slovenkaAvailability();
