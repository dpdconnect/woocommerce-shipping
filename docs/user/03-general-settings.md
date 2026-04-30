<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# General Settings

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find This Page

**WooCommerce → DPD Settings → General**

## Fields

### DPD Account Type

| Value | Description |
|---|---|
| `B2C` | Business-to-Consumer — deliveries to private addresses. |
| `B2B` | Business-to-Business — deliveries to commercial addresses. |

Select the account type that matches your DPD contract.

### DPD Depot

Your DPD depot number. This value is included in every shipment request as `sendingDepot`. Contact DPD to obtain your depot number.

### DPD Label Format

| Value | Description |
|---|---|
| `A4 format` | Standard A4 label sheet. Use with a regular printer. |
| `A6 format` | A6 thermal label. Use with a label printer (e.g. Zebra, Dymo). |

### DPD Send Tracking Email

| Value | Description |
|---|---|
| `DISABLED` | No tracking email is sent to customers after label creation. |
| `ENABLED` | A DPD-branded tracking email is automatically sent to the customer's billing email after a label is successfully created. |

The tracking email is in Dutch and contains the order number, delivery address, sender name, parcel barcodes, and a tracking link to `dpdgroup.com`.

### DPD Download Format

Controls the file format when downloading multiple labels at once.

| Value | Description |
|---|---|
| `Zip file` | Labels are bundled into a single `.zip` archive containing individual PDF files. |
| `Merged PDF file` | All labels are merged into a single PDF file. |

This setting only affects **bulk** downloads. Single-label downloads always produce a PDF.

### DPD Default Package Type

Sets the parcel dimensions used in shipment requests when no specific volume is provided.

| Value | Internal Code | Description |
|---|---|---|
| `Small Parcel` | `015010010` | Dimensions: 15 × 10 × 10 cm |
| `Normal Parcel` | `100050050` | Dimensions: 100 × 50 × 50 cm |

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
