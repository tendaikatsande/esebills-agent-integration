=== EseBills Agent Integration ===
Contributors: esebills
Donate link: https://esebills.co.zw
Tags: esebills, pesepay, payments, airtime, utility
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate EseBills Agent API to sell utility tokens, airtime, and data bundles — all payments via Pesepay. No agent wallet debits.

== Description ==

This plugin connects your WordPress frontend to the EseBills Agent API, allowing approved agents to:

* **Browse products** — `[esebills_products]` shows a catalog of all available products grouped by category.
* **Buy with one click** — each product links to the checkout with its code pre-loaded.
* **Process purchases via Pesepay** (card / mobile money) — only the customer is charged, never the agent's wallet.
* Validate customer details (meter numbers, account numbers) before taking payment.
* Receive fulfilment results (tokens, vouchers, receipts).
* Earn commissions automatically on every sale.

**Why Pesepay-only?** Customers pay directly through Pesepay, eliminating the risk of agent wallet exploitation. Your wallet balance stays untouched.

== Installation ==

1. Upload the `esebills-agent-integration` folder to `/wp-content/plugins/`, or upload the zip via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings > EseBills Agent** and enter your live `X-API-Key` from your EseBills agent dashboard.
4. Add the shortcodes to your pages:

   **Products catalog page** — shows all products grouped by category:

   `[esebills_products]`

   **Checkout page** — displays the purchase form for a selected product:

   `[esebills_checkout]`

   When a customer clicks a product in the catalog, they are taken to the checkout page with that product pre-selected. For best results, put the two shortcodes on separate pages.

== Frequently Asked Questions ==

= What is the EseBills Agent API? =

The Agent API lets approved EseBills agents sell utility tokens, airtime, data bundles, and more without integrating directly with suppliers. This plugin provides a ready-to-use frontend for that API.

= Why don't you support EseWallet? =

EseWallet debits the agent's wallet for customer purchases, which creates credit risk. Pesepay ensures the customer pays directly, keeping your wallet balance safe. This is the recommended payment flow.

= How do I get an API key? =

Generate your key from the EseBills agent dashboard at https://dashboard.esebills.co.zw. It requires an approved agent account.

= Which products can I sell? =

Any product available through the EseBills Agent API — ZESA tokens, TelOne broadband, DSTV subscriptions, Econet bundles, and more. Use the `countryCode` parameter to filter by market.

= Can I show a product catalog instead of a single product? =

Yes. Use `[esebills_products]` on any page. It fetches all active products from the API and groups them by category (Mobile, Utilities, TV, etc.). Each product links to your checkout page with the product code pre-loaded.

You can filter by country:

`[esebills_products country="ZW"]`

And link products to a specific checkout page:

`[esebills_products checkout_url="https://yoursite.com/checkout/"]`

= Is this plugin translation-ready? =

Yes. All user-facing strings use WordPress i18n functions with the `esebills-agent-integration` text domain. Contribute translations via WordPress.org.

== Screenshots ==

1. Checkout form with branded green UI — customers select a product option and pay via Pesepay.

== Changelog ==

= 1.2.0 =
* New `[esebills_products]` shortcode — full product catalog grouped by category.
* `[esebills_checkout]` now reads product code from URL (?product_code=XXXX) when omitted.
* Back link shown on checkout when no product is selected.
* Country filter and custom checkout URL support for the catalog.
* Product cards added to brand CSS.

= 1.1.0 =
* Pesepay-only payments — no agent wallet debits, preventing credit exploitation.
* Branded checkout UI with EseBills green theme (Manrope / Bricolage Grotesque / Space Mono).
* Improved form UX: field labels, hints, required markers, and focus states.
* Assets are now enqueued properly via `wp_enqueue_style`.
* Text domain loading added for i18n readiness.

= 1.0.0 =
* Initial release with EseWallet and Pesepay dual-payment support.
* Product discovery, validation, and checkout via shortcode.

== Upgrade Notice ==

= 1.2.0 =
New `[esebills_products]` shortcode available. Add it to a page to let customers browse products by category before checkout. No breaking changes.

= 1.1.0 =
This release removes EseWallet and switches to Pesepay-only payments. Review your checkout pages — the payment method selector is removed. All purchases now go through Pesepay automatically.
