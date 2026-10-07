# WooCommerce theme handoff

This is a static HTML design preview. Product prices, delivery fees, payment choices and orders are examples. It does not charge, email, reserve stock or create WooCommerce orders. Checkout details and the last 20 preview receipts are stored only in `sessionStorage`; the cart and selected sample delivery method use `localStorage`.

| Page | WooCommerce role | Design content |
| --- | --- | --- |
| `cart.html`, `cart-en.html` | Designated Cart page | Product/variation, price, editable quantity, remove, coupon entry, subtotal, shipping selection, taxes placeholder, total and checkout link |
| `checkout.html`, `checkout-en.html` | Designated Checkout page | Contact, shipping address, optional separate billing address, shipping choice, payment choice, notes, policies, order summary and place order |
| `order.html`, `order-en.html` | Order received / confirmation endpoint | Preview status, order number/date/email/payment/total, item table, billing and shipping addresses, delivery and notes |

For a modern block theme, recreate these layouts with the Cart, Checkout and Order Confirmation blocks. Configure Cart/Checkout pages in WooCommerce settings. The confirmation is an order-received endpoint in WooCommerce, not an independently configured checkout page. The static `preview_order` query parameter is only a receipt identifier for this demo.

For a classic PHP theme, WooCommerce supplies `cart/cart.php`, `cart/cart-totals.php`, `checkout/form-checkout.php`, `checkout/review-order.php`, `checkout/payment.php`, `checkout/thankyou.php`, `order/order-details.php` and `order/order-details-customer.php`. Preserve their hooks and WooCommerce nonces rather than copying the static JavaScript. Relevant hooks include `woocommerce_before_cart`, `woocommerce_cart_contents`, `woocommerce_proceed_to_checkout`, `woocommerce_before_checkout_form`, `woocommerce_checkout_billing`, `woocommerce_checkout_shipping`, `woocommerce_checkout_order_review`, `woocommerce_review_order_before_submit` and `woocommerce_thankyou`. Classic template hooks are not a substitute for block extension APIs.

The preview uses standard field names: `billing_email`, `billing_phone`, `billing_*` / `shipping_*` with `first_name`, `last_name`, `company`, `country`, `address_1`, `address_2`, `city`, `state`, `postcode`; plus `order_comments`, `payment_method`, `terms`, and `coupon_code`. The preview checkbox `billing_same_as_shipping` is a local UI choice; use the chosen WooCommerce checkout implementation's native address state. Country-specific address requirements must come from WooCommerce rather than the static BA-only example.

Replace `commerce.js` and the exposed `SlovenkaCommerce` adapter with WooCommerce's native cart/session and checkout handling. WooCommerce must own validated product variations, stock, quantity constraints, coupon validation, taxes, shipping zones/methods, gateway availability, order persistence and payment status. `flat_rate`, `local_pickup`, `cod` and `bacs` here are illustrative method identifiers, not activated gateways or configured instance IDs. Coupons currently show a truthful message and apply no discount. Taxes are explicitly uncalculated, not presented as zero or included VAT. Confirm sales policies and actual pickup/delivery terms before launch. Do not reuse the browser preview order store for real customer orders.

No payment collection or separate order-pay flow is simulated. The eventual WooCommerce gateway must use WooCommerce's secured order-pay/order-received endpoints and access controls. The receipt screen never creates an order on load or reload.

Official sources reviewed on 6 October 2026:

- [Customizing Cart and Checkout pages](https://woocommerce.com/document/woocommerce-store-editing/customizing-cart-and-checkout/)
- [Checkout block: contact/address fields, shipping, payment, notes, policies and totals](https://woocommerce.com/document/woocommerce-store-editing/customizing-cart-and-checkout/checkout-block/)
- [Order Confirmation structure](https://woocommerce.com/document/woocommerce-store-editing/templates/customizing-order-confirmation-page/)
- [WooCommerce block reference](https://developer.woocommerce.com/docs/block-development/reference/block-references/)
- [WooCommerce checkout field names and country-specific fields](https://developer.woocommerce.com/docs/customizing-checkout-fields-using-actions-and-filters/)
- [Official classic order-details template](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/templates/order/order-details.php)
