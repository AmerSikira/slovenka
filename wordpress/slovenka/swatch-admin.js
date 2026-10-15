"use strict";
(($) => {
  const init = () => {
    $('.slovenka-color-field').not('.wp-color-picker').wpColorPicker();
    $('.slovenka-local-swatches').each(function () {
      $(this).find('input, select, textarea').addClass('optional_attribute_or_variation_data');
      $(this).find('.slovenka-local-colors').prop('hidden', $(this).find('.slovenka-swatch-type').val() !== 'color');
    });
    $.maybe_disable_save_button?.();
  };
  $(init);
  $(document.body).on('woocommerce_added_attribute woocommerce_attributes_saved', init);
  $(document).on('change', '.slovenka-swatch-type', init);
  $(document).ajaxSuccess((_event, _xhr, settings) => {
    if (typeof settings.data === 'string' && new URLSearchParams(settings.data).get('action') === 'add-tag') {
      const field = $('#addtag .slovenka-color-field');
      if (field.val() === '') field.closest('.wp-picker-container').find('.wp-picker-clear').trigger('click');
    }
  });
})(jQuery);
