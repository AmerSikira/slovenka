"use strict";
// Presentation only. WordPress and WooCommerce own navigation/search/cart/form state.
(() => {
  const menuButton = document.querySelector('.mobile-menu');
  const navigation = document.getElementById('site-navigation');
  const search = document.getElementById('site-search');
  const openSearch = document.querySelector('[data-open-search]');
  menuButton?.addEventListener('click', () => {
    const expanded = menuButton.getAttribute('aria-expanded') !== 'true';
    menuButton.setAttribute('aria-expanded', String(expanded));
    navigation?.classList.toggle('is-open', expanded);
  });
  openSearch?.addEventListener('click', () => search?.showModal());
  document.querySelector('[data-close-search]')?.addEventListener('click', () => search?.close());
  search?.addEventListener('click', event => { if (event.target === search) search.close(); });
  search?.addEventListener('close', () => openSearch?.focus());
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') { menuButton?.setAttribute('aria-expanded','false'); navigation?.classList.remove('is-open'); }
  });
  document.addEventListener('submit', async event => {
    const form = event.target;
    if (!form.matches('.project-form, [data-contact-form]')) return;
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = form.querySelector('button[type="submit"], button.button');
    if (button?.disabled) return;
    const en = form.querySelector('[name="lang"]')?.value === 'en';
    const original = button?.textContent;
    if (button) { button.disabled = true; button.textContent = en ? 'Sending…' : 'Slanje…'; }
    form.querySelector('.form-error')?.remove();
    const data = new FormData(form);
    const services = data.getAll('services'); data.delete('services');
    for (const service of services) data.append('services[]', service);
    data.set('nonce', window.SlovenkaForms.nonce);
    try {
      const response = await fetch(window.SlovenkaForms.ajaxUrl, {method:'POST',credentials:'same-origin',body:data});
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.data?.message || (en ? 'Could not send your inquiry. Please try again.' : 'Upit nije poslan. Pokušajte ponovo.'));
      const panel = document.createElement('div'); panel.className='success-panel'; panel.setAttribute('role','status');
      const title=document.createElement('h2'); title.textContent=en ? 'Thank you for your inquiry.' : 'Hvala na vašem upitu.';
      const message=document.createElement('p'); message.textContent=result.data.message;
      const reference=document.createElement('p'); reference.textContent=(en ? 'Reference: ' : 'Referenca: ') + result.data.reference;
      panel.append(title,message,reference); form.replaceWith(panel); panel.scrollIntoView({block:'center'});
    } catch (error) {
      const message=document.createElement('p'); message.className='form-error'; message.setAttribute('role','alert'); message.textContent=error.message;
      button ? button.before(message) : form.append(message);
      if (button) { button.disabled=false; button.textContent=original; }
    }
  });
})();
