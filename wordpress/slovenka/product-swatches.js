"use strict";
// WooCommerce remains the source of truth for values, stock and variation lookup.
(($) => {
  const enhanced = new WeakSet();
  function enhance(form) {
    if (enhanced.has(form)) return;
    enhanced.add(form);
    const controls = [];
    form.querySelectorAll('select[data-slovenka-swatches]').forEach(select => {
      const options = [...select.options].filter(option => option.value);
      if (!options.length) return;
      const group = document.createElement('div');
      group.className = 'product-swatches';
      group.setAttribute('role', 'group');
      const label = [...form.querySelectorAll('label')].find(label => label.htmlFor === select.id);
      if (label) {
        if (!label.id) label.id = `${select.id}-swatch-label`;
        group.setAttribute('aria-labelledby', label.id);
      } else {
        group.setAttribute('aria-label', select.getAttribute('aria-label') || select.name);
      }
      const colors = JSON.parse(select.dataset.swatchColors || '{}');
      const buttons = options.map(option => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'product-swatch';
        button.dataset.value = option.value;
        button.textContent = option.textContent;
        button.setAttribute('aria-pressed', 'false');
        button.title = option.textContent;
        const color = colors[option.value.toLowerCase()];
        if (select.dataset.slovenkaSwatches === 'color' && /^#[0-9a-f]{3,6}$/i.test(color || '')) {
          button.classList.add('product-swatch-color');
          button.style.setProperty('--swatch-color', color);
          button.setAttribute('aria-label', option.textContent);
        }
        button.addEventListener('click', () => {
          select.value = select.value === button.dataset.value ? '' : button.dataset.value;
          $(select).trigger('change');
        });
        group.append(button);
        return button;
      });
      group.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        const available = buttons.filter(button => !button.disabled);
        const index = available.indexOf(document.activeElement);
        if (index < 0) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? available.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + available.length) % available.length;
        available[next].focus();
      });
      const selected = document.createElement('span');
      selected.className = 'swatch-selection';
      selected.setAttribute('aria-live', 'polite');
      select.after(group, selected);
      // Keep the successful form control and validation, with the buttons as UI.
      select.classList.add('swatch-select');
      select.tabIndex = -1;
      select.setAttribute('aria-hidden', 'true');
      select.addEventListener('invalid', () => buttons.find(button => !button.disabled)?.focus());
      label?.addEventListener('click', event => {
        event.preventDefault();
        (buttons.find(button => button.getAttribute('aria-pressed') === 'true' && !button.disabled) || buttons.find(button => !button.disabled))?.focus();
      });
      controls.push({ select, buttons, selected });
    });
    const sync = () => controls.forEach(({ select, buttons, selected }) => {
      const options = new Map([...select.options].map(option => [option.value, option]));
      buttons.forEach(button => {
        const option = options.get(button.dataset.value);
        button.disabled = select.disabled || !option || option.disabled;
        button.setAttribute('aria-pressed', String(select.value === button.dataset.value));
      });
      selected.textContent = select.dataset.slovenkaSwatches === 'color' && select.value ? select.selectedOptions[0]?.textContent || '' : '';
    });
    $(form).on('change.slovenkaSwatches woocommerce_update_variation_values.slovenkaSwatches reset_data.slovenkaSwatches found_variation.slovenkaSwatches', sync);
    sync();
  }
  document.querySelectorAll('form.variations_form').forEach(enhance);
  $(document).on('wc_variation_form.slovenkaSwatches', 'form.variations_form', function () { enhance(this); });
})(jQuery);
