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
}, true);

// Persist production/contact forms in WordPress; prevent the static browser-only handler.
document.addEventListener('submit', async event => {
  const form = event.target;
  if (!form.matches('.project-form, [data-contact-form]')) return;
  event.preventDefault();
  event.stopImmediatePropagation();
  if (!form.reportValidity()) return;
  const button = form.querySelector('button[type="submit"], button.button');
  if (button?.disabled) return;
  const en = document.documentElement.lang === 'en';
  const original = button?.innerHTML;
  if (button) { button.disabled = true; button.textContent = en ? 'Sending…' : 'Slanje…'; }
  form.querySelector('.form-error')?.remove();
  const data = new FormData(form);
  const services = data.getAll('services');
  data.delete('services');
  for (const service of services) data.append('services[]', service);
  data.set('action', 'mk_inquiry');
  data.set('nonce', window.SlovenkaSite.inquiryNonce);
  data.set('kind', form.matches('[data-contact-form]') ? 'contact' : 'inquiry');
  data.set('lang', en ? 'en' : 'bs');
  try {
    const response = await fetch(window.SlovenkaSite.ajaxUrl, {method:'POST', credentials:'same-origin', body:data});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.data?.message || (en ? 'Could not send your inquiry. Reload the page and try again.' : 'Upit nije poslan. Osvježite stranicu i pokušajte ponovo.'));
    const panel = document.createElement('div');
    panel.className = 'success-panel'; panel.setAttribute('role','status');
    const title = document.createElement('h2'); title.textContent = en ? 'Thank you for your inquiry.' : 'Hvala na vašem upitu.';
    const message = document.createElement('p'); message.textContent = result.data.message;
    const reference = document.createElement('p'); reference.textContent = `${en ? 'Reference' : 'Referenca'}: ${result.data.reference}`;
    panel.append(title, message, reference); form.replaceWith(panel);
    panel.scrollIntoView({block:'center'});
  } catch (error) {
    const message = document.createElement('p'); message.className='form-error'; message.setAttribute('role','alert');
    message.textContent = error.message || (en ? 'Connection failed. Please try again.' : 'Veza nije uspjela. Pokušajte ponovo.');
    button ? button.before(message) : form.append(message);
    if (button) { button.disabled=false; button.innerHTML=original; }
  }
}, true);
