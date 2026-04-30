<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Tracking Email

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Overview

When **Send Tracking Email** is set to `ENABLED` in General Settings, the plugin automatically sends a DPD-branded email to the customer after a label is successfully created.

## When Is It Sent?

| Trigger | Timing |
|---|---|
| **Sync label creation** | Immediately after label(s) are downloaded |
| **Async label creation** | When DPD sends the success callback for each job |

> Return labels do NOT trigger tracking emails (only regular labels send tracking emails in the async callback flow, and only if the job was in `QUEUED` status when the callback arrived).

## Email Content

The email is rendered from `classes/Handlers/trackingemail/index.php` — a static PHP HTML template.

### Content Blocks

| Section | Content |
|---|---|
| Logo | DPD logo loaded from `https://www.dpd.com/...` |
| Heading | "Je pakket van bestelling #[order_id] wordt verstuurd" (Dutch) |
| Greeting | "Hallo [customer name]" |
| Body text | Fixed Dutch text about the package being submitted to DPD |
| Delivery address | Shipping address from order, or "Parcelshop" for parcelshop deliveries |
| Sender | Company name from plugin settings |
| Barcodes | DPD parcel numbers (one per line) |
| Tracking button | Button linking to `dpdgroup.com` tracking page per parcel number |
| Footer | "Met vriendelijke groet, Team DPD" |

### Variables Used in Template

| Variable | Source | Description |
|---|---|---|
| `$orderId` | Order ID | WooCommerce order number |
| `$data['shipment']['receiver']['name1']` | Shipment payload | Customer full name |
| `$data['shipment']['receiver']['street']` | Shipment payload | Street address |
| `$data['shipment']['receiver']['postalcode']` | Shipment payload | Postal code (or `postalCode` as fallback) |
| `$data['shipment']['receiver']['city']` | Shipment payload | City |
| `$data['shipment']['sender']['name1']` | Shipment payload | Sender company name |
| `$data['shipmentType']` | `$dpdProduct['type']` | `parcelshop` shows "Parcelshop" instead of address |
| `$data['parcelNumbers']` | Label response | Array of DPD parcel barcodes |

## Email Sending

The email is sent via `wp_mail()`:

```php
$headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $senderName . ' <' . $senderEmail . '>',
];
wp_mail($billingEmail, __("Je bestelling is gereed voor verzending"), $emailContent, $headers);
```

- **To:** Customer's billing email address (`order->get_billing_email()`)
- **From:** Company name + email from Company Settings
- **Subject:** "Je bestelling is gereed voor verzending" (Dutch — hardcoded, translatable)
- **Format:** HTML

## Localization

The email subject and body are in Dutch by default. The template uses `__()` for localizable strings. To provide English or other translations, add a `.po`/`.mo` file for the `dpdconnect` text domain.

## Customizing the Template

The template file is `classes/Handlers/trackingemail/index.php`. It is a plain PHP+HTML file — edit it directly to customize the email layout.

> **Note:** This file is overwritten on plugin updates. Keep a backup or consider using a child plugin to override the template.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
