<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Company Settings

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find This Page

**WooCommerce → DPD Settings → Company**

## Purpose

Company information is used as the **sender** on every label request. All fields are sent to DPD Connect as part of the shipment payload.

## Fields

| Field | Required | Notes |
|---|---|---|
| **Company name** | Yes | Printed on the label as the sender name (`name1`). |
| **Company address** | Yes | Street address of your warehouse or dispatch location. |
| **Company postal code** | Yes | Postal code — no spaces for most countries. |
| **Company city** | Yes | City of your dispatch location. |
| **Company country code** | Yes | **ISO 3166-1 alpha-2** country code. Examples: `NL`, `BE`, `DE`. Must be exactly 2 uppercase letters. |
| **Company phone number** | Yes | Used for DPD contact in case of delivery issues. |
| **Company email** | Yes | Shown as the sender email in tracking notifications. |
| **VAT number** | Conditional | Required for B2B shipments and international customs. |
| **EORI number** | Conditional | Required for shipments outside the EU. Dots are automatically stripped before sending. |
| **HMRC number** | Conditional | UK-specific reference number (HMRC/SPR). Required for UK shipments post-Brexit. |
| **Customs terms** | Yes | Defines who pays duties and taxes for international shipments. |

## Customs Terms

| Value | Code | Description |
|---|---|---|
| DAP DP — D&T paid by sender | `DAPDP` | Sender pays all duties and taxes. Customer receives parcel without additional charges. |
| DAP NP — D&T paid by receiver | `DAPNP` | Customer pays duties and taxes on delivery. |

If no customs terms are saved, the system defaults to `DAPDP`.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
