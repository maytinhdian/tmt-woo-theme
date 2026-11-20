# CLAUDE.md

WooCommerce theme "TMT Shop" (`tmt-shop/`), ported from the web shop (Storefront module) of the Laravel app maytinhdian/quan-ly-cua-hang. The owner works in Vietnamese: user-facing text and docs in Vietnamese, code identifiers in English. Sold/shared to customers who only know WordPress admin, so everything a shop owner changes must be in the Customizer, never in code.

## Layout

- `functions.php` loads `inc/`: `options.php` (defaults `tmt_defaults()`, read with `tmt_opt('group.key')` = theme_mod `tmt_group_key`; list options are JSON, read with `tmt_opt_list()`), `setup.php` (supports, menus, assets, inline CSS vars), `template-tags.php`, `menu.php` (main menu, mega menu, nav effects), `home.php`, `customizer.php` + `customizer-controls.php` (repeater + sortable controls), and with WooCommerce `woocommerce.php` + `filters.php` (GET filters `hang[]`, `gia`, `con-hang`, `ts[attr]`, `sap-xep`).
- Every product grid calls `tmt_product_card()` (`template-parts/product/card.php`); never write another card.
- Home sections = `template-parts/home/<key>.php`, keys from `tmt_home_sections()`.
- CSS `assets/css/theme.css` is copied from the app's storefront styles (same class names `sf-*`) plus a WP section at the end. JS: `assets/js/theme.js` (all pages), `assets/js/product.js` (product page, global Swiper).
- Vendored libraries live in `assets/vendor/` (no CDN except Google Fonts / Material Icons).
- The PC builder ("Lên cấu hình") will be a separate plugin `tmt-shop-tools`; the theme links to it through the filter `tmt_pc_builder_url`.

## Testing

No build step. Lint with `php -l`, then run a WordPress + WooCommerce site with the theme active and check pages with WP_DEBUG on (no notices) and Playwright screenshots at desktop and phone width.
