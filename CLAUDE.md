# CLAUDE.md

WooCommerce theme "TMT Shop" (`tmt-shop/`), ported from the web shop (Storefront module) of the Laravel app maytinhdian/quan-ly-cua-hang. The owner works in Vietnamese: user-facing text and docs in Vietnamese, code identifiers in English. Sold/shared to customers who only know WordPress admin, so everything a shop owner changes must be in the Customizer, never in code.

## Layout

- `functions.php` loads `inc/`: `options.php` (defaults `tmt_defaults()`, read with `tmt_opt('group.key')` = theme_mod `tmt_group_key`; list options are JSON, read with `tmt_opt_list()`), `setup.php` (supports, menus, assets, inline CSS vars), `template-tags.php`, `menu.php` (main menu, mega menu, nav effects), `home.php`, `customizer.php` + `customizer-controls.php` (repeater + sortable controls), and with WooCommerce `woocommerce.php` + `filters.php` (GET filters `hang[]`, `gia`, `con-hang`, `ts[attr]`, `sap-xep`).
- Every product grid calls `tmt_product_card()` (`template-parts/product/card.php`); never write another card.
- Home sections = `template-parts/home/<key>.php`, keys from `tmt_home_sections()`.
- CSS `assets/css/theme.css` is copied from the app's storefront styles (same class names `sf-*`) plus a WP section at the end. JS: `assets/js/theme.js` (all pages), `assets/js/product.js` (product page, global Swiper).
- Cart + checkout = one page (cart redirects to checkout when not empty): `inc/checkout.php` (fields, shipping helpers, block pages → shortcodes), templates `woocommerce/checkout/form-checkout.php` + `review-order.php` + `cart/cart-empty.php`, styles `assets/css/checkout.css`, JS `assets/js/checkout.js`. Quantity/coupon controls submit the hidden forms `#tmt-qty-form` / `#tmt-coupon-form` via the `form=` attribute.
- Bank-transfer QR: `inc/payment-qr.php` only (VietQR payload + SVG via `inc/vendor/qrcode.php`, account = first WooCommerce BACS account); call `tmt_order_qr_box($order)`, never build VietQR content elsewhere. Order lookup: `inc/order-lookup.php` (`[tmt_tra_don]`). Product-page promo banners: Customizer `product.promos`, `inc/promos.php`, render with `tmt_promos($position)`.
- Vendored libraries live in `assets/vendor/` (no CDN except Google Fonts / Material Icons).
- The PC builder ("Xây dựng cấu hình") is the plugin `tmt-shop-tools/` (same repo), ported from the app's `app/Modules/PcBuilder` (web mode only): `includes/slots.php` (12 slots, spec keys = WooCommerce global attributes `pa_<key with ->`, aliases), `catalog.php` (parts from products/variations in slot categories, cached transient), `checker.php` (the 22 rules, keep in sync with the app's CompatibilityChecker), `builder.php` (shortcode `[tmt_len_cau_hinh]`, REST `tmt/v1/cau-hinh/*`, add-whole-build-to-cart with server re-check, presets, A5 print `?in-cau-hinh=`), `admin.php` (WooCommerce → Lên cấu hình). Never send stock numbers to the browser. The theme links to it through the filter `tmt_pc_builder_url`; pages opt into full width with the filter `tmt_page_wide`.

## Testing

No build step. Lint with `php -l`, then run a WordPress + WooCommerce site with the theme active and check pages with WP_DEBUG on (no notices) and Playwright screenshots at desktop and phone width.
