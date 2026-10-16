# WooCommerce Bosnian translation

`woocommerce-bs_BA.mo` is the WordPress.org community translation of WooCommerce,
exported 15 October 2026 from:
https://translate.wordpress.org/projects/wp-plugins/woocommerce/stable/bs/default/export-translations/?format=mo

The accompanying `woocommerce-bs_BA.po` is the editable source, exported from:
https://translate.wordpress.org/projects/wp-plugins/woocommerce/stable/bs/default/export-translations/?format=po

Translation contributors: the WordPress.org Bosnian translation team.
WooCommerce and its translations are distributed under GPL-3.0-or-later:
https://github.com/woocommerce/woocommerce/blob/trunk/LICENSE

This fallback is used only on Bosnian storefront requests. An installed
`wp-content/languages/plugins/woocommerce-bs_BA.mo` takes precedence.
Update the bundled export from the source above when updating WooCommerce.

The upstream catalogue is incomplete. `storefront-bs.json` contains Slovenka's
reviewed supplemental storefront translations, including plural forms. It is
used for PHP display strings and combined with the PO by
`scripts/build-translations.py` to produce per-source-file Jed JSON catalogues.
The theme builder runs this step automatically (requires Python `polib`).
Generated `woocommerce-js/*.json` files are packaged but ignored by Git.

Installed script catalogues retain nonempty translations; missing entries fall
back to the bundled catalogue. PHP supplements cover the documented storefront
terms even when an installed catalogue lacks them. Administrator strings and
merchant-authored product copy, policy text, shipping/payment method titles and
category names remain under the merchant's normal content workflow.
English uses WooCommerce's source strings. Product copy is merchant content.
