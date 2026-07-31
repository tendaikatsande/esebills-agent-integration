=== EseBills Agent Integration ===
Contributors: esebills
Tags: esebills, pesepay, airtime, zesa, payments
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.2.0
License: GPLv2 or later

Integrate EseBills Agent API into your WordPress site to sell utility tokens, airtime, and data bundles — all payments processed securely via Pesepay.

== Description ==

This plugin connects your WordPress frontend to the EseBills Agent API, allowing approved agents to:

- Render dynamic product checkout forms.
- Process purchases via **Pesepay** (card / mobile money) — only the customer is charged.
- Validate customer details and receive fulfilment results.
- Include commission-ready transactions automatically.

== Installation ==

1. Upload `esebills-agent-integration.zip` via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin.
3. Go to **Settings > EseBills Agent** and set your `X-API-Key`.
4. Add `[esebills_checkout product_code="ECONET_BUNDLES_USD"]` to any page.

== Changelog ==

= 1.1.0 =
- Pesepay-only payments — no agent wallet debits.
- Branded checkout UI with EseBills green theme.
- Improved form UX with field labels, hints, and validation.
