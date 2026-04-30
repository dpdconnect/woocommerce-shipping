<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Shipping Methods

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Overview

DPD Connect registers a custom WooCommerce shipping method called **DPD Shipping Method** (`dpd_shipping_method`). It is a zone-based method, meaning you add and configure it per shipping zone, just like WooCommerce's built-in flat rate or free shipping methods.

Each shipping zone can have **multiple DPD method instances**, one per DPD product type (e.g. one for standard delivery, one for parcelshop, one for Saturday delivery).

## Adding DPD to a Shipping Zone

1. Go to **WooCommerce → Settings → Shipping → Shipping Zones**.
2. Click **Edit** on an existing zone, or add a new zone.
3. Click **Add shipping method** and select **DPD Shipping Method** from the list.
4. Click **Edit** (pencil icon) on the newly added method to configure it.

## Method Settings

### Method Title

The label shown to customers during checkout (e.g. `DPD Standard`, `DPD Parcelshop`). The title is automatically set to the DPD product name when you save; you cannot freely rename it to avoid mismatches with label generation.

### Tax Status

Whether the shipping cost is taxable.

| Value | Description |
|---|---|
| Taxable | Shipping cost is subject to tax. |
| None | Shipping cost is tax-exempt. |

### Cost

Flat shipping cost. Supports dynamic expressions using shortcodes:

| Shortcode | Description |
|---|---|
| `[qty]` | Number of items in the cart. |
| `[cost]` | Total item cost in the cart. |
| `[fee percent="10" min_fee="2" max_fee="20"]` | Percentage-based fee with optional min/max cap. |

Examples:
- `5.00` — fixed €5 cost
- `2.00 * [qty]` — €2 per item
- `[fee percent="5" min_fee="3"]` — 5% of cart value, minimum €3

### DPD Method Type (Product Code)

The DPD product that this shipping method instance maps to. Available product codes are fetched live from the DPD API using your credentials. Common options include standard delivery, parcelshop, predict, Saturday, and B2B variants.

### Checkout Description

A description shown to customers on the checkout page below the method title.

### Shipping Class Costs

If your store uses WooCommerce shipping classes, you can set a cost per shipping class on top of the base rate. The **Calculation type** controls how class costs are combined:

| Type | Description |
|---|---|
| Per class | Each class is charged separately; total = sum of all class costs. |
| Per order | Only the most expensive class cost is applied. |

## Saturday Delivery Settings

When a DPD product of type Saturday is selected, additional time-window settings appear:

| Setting | Description |
|---|---|
| **Show from day** | The day of the week from which Saturday delivery becomes visible at checkout (e.g. Friday). |
| **Show from time** | The time on that day from which Saturday delivery becomes visible. |
| **Show until day** | The last day of the week Saturday delivery is shown. |
| **Show until time** | The time on the last day until which Saturday delivery is shown. |

Outside of this configured window, the Saturday shipping method is automatically hidden from the checkout.

> **Time zone:** All Saturday delivery times are evaluated against the `Europe/Amsterdam` timezone.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
