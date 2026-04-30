<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Advanced Settings

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find This Page

**WooCommerce → DPD Settings → Advanced**

## Fields

### Connect URL

The base URL of the DPD Connect API endpoint. Leave blank to use the DPD default. Only change this if DPD has instructed you to use a specific environment (e.g. a staging endpoint).

Default: the value defined by the SDK's `Client::ENDPOINT` constant.

### Callback URL

The URL to which DPD sends asynchronous job results. Used for **async label generation** (when the batch size exceeds the async threshold).

- If left blank, the plugin auto-generates a URL based on `admin_url()`:
  `https://yoursite.com/wp-admin/admin-post.php?action=dpdbatch`
- Set a custom URL if your WordPress admin URL differs from the publicly accessible admin URL (e.g. behind a reverse proxy, or when using a staging domain).

### Async Threshold

The maximum number of orders that will be processed **synchronously** in a single bulk label request. If the number of selected orders **exceeds** this threshold, the request is processed asynchronously (jobs are queued, DPD processes them and sends results via the Callback URL).

| Setting | Description |
|---|---|
| Default | 10 |
| Maximum | 10 |
| Minimum | 1 |

**Synchronous** (≤ threshold): Labels are generated immediately and downloaded as PDF/ZIP.

**Asynchronous** (> threshold): A batch is created, you are redirected to the Jobs overview page. DPD processes the labels and calls back the plugin when done.

### Generate Shipping Label on Processing

**Checkbox** — when enabled, a DPD shipping label is automatically created as soon as an order's status changes to **Processing** (e.g. immediately after a successful payment).

- The plugin checks whether the order uses a DPD shipping method.
- If the DPD method matches an available product, the label is created for that product type.
- If **Generate return label on label creation** is also enabled, a return label is created alongside the shipping label.

### Generate Return Label on Label Creation

**Checkbox** — when enabled, a DPD return label is automatically created every time a shipping label is created (regardless of whether it was triggered manually or automatically).

> **Note:** Return labels use the `RETURN` product type. Enabling both auto-generate options will always produce a shipping + return label pair.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
