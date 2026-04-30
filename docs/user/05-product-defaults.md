<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Product Defaults

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find This Page

**WooCommerce → DPD Settings → Products**

## Purpose

These settings provide **fallback values** used in shipment and customs declarations when a product does not have individual DPD attributes configured. They apply to all orders unless overridden per-product (see [Product Shipping Attributes](12-product-attributes.md)).

## Fields

### Default Harmonized System Code

The **HS code** (also called a tariff code) is required for international shipments for customs classification. It is a 6–10 digit number defined by the World Customs Organization.

Example: `8471.30` (portable computers)

If a product does not have its own HS code set, this default value is used in the customs declaration (`harmonizedSystemCode` field).

### Default Country of Origin

The country where the products were manufactured. Must be an **ISO 3166-1 alpha-2** country code.

Examples: `NL`, `CN`, `DE`

Used as the `originCountry` in customs lines when no per-product country of origin is set.

### Default Product Weight (kg)

The fallback weight in kilograms when a WooCommerce product has no weight set.

The plugin converts this value to DPD's internal weight unit (decagrams × 100) automatically, based on the WooCommerce weight unit setting (`kg`, `g`, `lbs`, `oz`).

If an order's total product weight calculates to zero, this default weight is used (multiplied by 100 to convert kg → DPD units).

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
