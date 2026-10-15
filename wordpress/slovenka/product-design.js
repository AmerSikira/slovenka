"use strict";
(() => {
  const panel = document.getElementById('product-measurements');
  if (panel && typeof HTMLDialogElement !== 'undefined') {
    const dialog = document.createElement('dialog');
    dialog.className = 'modal product-size-guide';
    dialog.id = 'product-size-guide';
    dialog.setAttribute('aria-labelledby', 'product-measurements-title');
    const header = document.createElement('div');
    header.className = 'modal-head';
    header.append(panel.querySelector('h2'));
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'icon-button';
    close.textContent = '×';
    close.setAttribute('aria-label', document.documentElement.lang.startsWith('bs') ? 'Zatvori' : 'Close');
    close.autofocus = true;
    header.append(close);
    dialog.append(header, panel);
    document.body.append(dialog);
    let trigger;
    document.querySelectorAll('.product-size-guide-trigger').forEach(link => {
      link.setAttribute('aria-haspopup', 'dialog');
      link.setAttribute('aria-controls', dialog.id);
      link.addEventListener('click', event => {
        event.preventDefault();
        trigger = link;
        const size = document.querySelector('select[data-slovenka-swatches="size"]')?.value;
        panel.querySelectorAll('[data-measurement-size]').forEach(row => row.classList.toggle('selected', Boolean(size) && row.dataset.measurementSize === size));
        dialog.showModal();
        document.documentElement.classList.add('size-guide-is-open');
      });
    });
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => {
      document.documentElement.classList.remove('size-guide-is-open');
      trigger?.focus({preventScroll:true});
    });
  }
  const revealAnchor = hash => {
    if (!hash || hash.length < 2) return;
    const target = document.getElementById(hash.slice(1));
    const details = target?.closest('details');
    if (details) { details.open = true; target.scrollIntoView({block:'nearest'}); }
  };
  revealAnchor(location.hash);
  window.addEventListener('hashchange', () => revealAnchor(location.hash));
  document.querySelectorAll('.woocommerce-review-link').forEach(link => link.addEventListener('click', () => revealAnchor(link.hash)));
})();
