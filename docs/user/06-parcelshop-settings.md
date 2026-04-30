<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Parcelshop Settings

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find This Page

**WooCommerce → DPD Settings → Parcelshop**

## Purpose

Configure the DPD parcelshop selector shown during checkout. When a customer selects a parcelshop-type shipping method, a fixed bottom bar appears with an interactive map to choose a nearby DPD parcel shop.

## Fields

### Google Maps API Key

Your own Google Maps API key. Used for:

- Rendering the map inside the parcelshop selector
- Converting postcodes/addresses to geographic coordinates

A "Show key" toggle button reveals the key value in plain text for verification.

> **When to use**: Required if you do not use DPD's shared key, or if you expect high checkout volume.

### Use DPD's Google Maps API Key

A checkbox that toggles between your own key and DPD's shared key.

| State | Behaviour |
|---|---|
| **Checked** | DPD's shared Google Maps key is used. The "Google Maps API Key" field is hidden. Suitable for low-traffic stores. |
| **Unchecked** | Your own Google Maps API Key field is shown and used. Recommended for high-volume stores to avoid rate limiting. |

> **Warning:** DPD's shared key may be subject to rate limiting. High-volume stores should configure their own Google Maps API key.

### Additional Parcelshop Shipping Methods

Allows non-DPD shipping methods (e.g. Table Rate Shipping, other plugins) to trigger the parcelshop selector at checkout.

- DPD shipping methods configured via WooCommerce Shipping Zones are **automatically detected** and do not need to be listed here.
- This setting is for **third-party** shipping methods that should also show the parcelshop picker.

The setting shows a scrollable list of all active WooCommerce shipping methods (excluding DPD methods). Tick any methods that should activate the parcelshop bar. The selected methods are stored as comma-separated method IDs (e.g. `table_rate:3,flat_rate:5`).

## How the Parcelshop Selector Works at Checkout

1. Customer reaches the checkout page.
2. If a parcelshop-type shipping method is selected, a fixed bottom bar labelled **"Choose your DPD pickup point"** slides up.
3. Clicking **Select DPD Parcel Shop** opens a full-screen map modal.
4. The map auto-centers on the shipping address entered by the customer.
5. After selecting a parcelshop, the bar updates to show the selected location, and the selection is saved to the session via AJAX.
6. On order placement, the parcelshop ID is saved to the order meta field `_dpd_parcelshop_id`.
7. If no parcelshop is selected when placing the order, an error message is shown and the order is blocked.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
