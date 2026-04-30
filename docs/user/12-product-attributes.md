<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Product Shipping Attributes

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find These Fields

**WooCommerce → Products → [Product] → Product data → Shipping tab**

The DPD Connect plugin adds a dedicated section to the **Shipping** tab of every WooCommerce product. These per-product values override the global defaults set in **DPD Settings → Products**.

---

## Fields

### Harmonized System Code

A customs tariff code (6–10 digits) used in international shipments to classify goods. Overrides the **Default Harmonized System Code** from Product Settings.

Example: `9030.39`

### Customs Value

The declared monetary value of the product for customs purposes (used to calculate import duties). Should be a numeric value in the store currency.

Example: `29.50`

### Country of Origin

ISO 3166-1 alpha-2 country code indicating where the product was manufactured. Overrides the **Default Country of Origin** from Product Settings.

Example: `NL`, `CN`, `DE`

### Age Check

**Checkbox** — when enabled, the shipment is flagged with `ageCheck: true` in the DPD label request. Used for products that require age verification on delivery (e.g. alcohol, tobacco — typically 18+ products).

If any item in an order has the age check enabled, the entire shipment will require age verification at delivery.

### Shipping Product

Determines whether this product requires a special DPD handling type:

| Option | Description |
|---|---|
| **Default** | No special handling; uses the standard shipping product. |
| **Fresh** | Product must be shipped via DPD Fresh (refrigerated) service. |
| **Freeze** | Product must be shipped via DPD Freeze (frozen) service. |

If any product in an order is set to Fresh or Freeze, the label generation flow changes: the customer is prompted to provide expiry dates per product type (Fresh/Freeze), and separate label parcels are created for each type.

### Carrier Description

A text description used in the DPD Fresh/Freeze parcel data (`goodsDescription` field). Required when Shipping Product is set to Fresh or Freeze.

---

## How These Values Are Used in Label Requests

When a label is created, the plugin reads the DPD attributes from each order item's product:

1. **HS code** → `customsLines[].harmonizedSystemCode`
2. **Customs value** → `customsLines[].totalAmount`
3. **Country of origin** → `customsLines[].originCountry`
4. **Age check** → `product.ageCheck` (true if any item has it enabled)
5. **Shipping product** → determines product type routing (Fresh/Freeze split)
6. **Carrier description** → `parcels[].goodsDescription` for Fresh/Freeze parcels

If a product does not have a specific value set, the global default from **DPD Settings → Products** is used.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
