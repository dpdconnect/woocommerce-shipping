<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Credentials

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Where to Find This Page

**WooCommerce → DPD Settings → Credentials**

## Fields

| Field | Description |
|---|---|
| **Connect Username** | Your DPD Connect API username (provided by DPD). |
| **Connect Password** | Your DPD Connect API password (provided by DPD). |

## How to Save

Fill in both fields and click **Save Settings**.

## Checking Your Credentials

The Credentials page includes a **Check Credentials** button. Click it to verify connectivity without saving. The check performs two tests:

1. **Authentication** — attempts to obtain a JWT token from the DPD Connect API.
2. **Products** — if authentication succeeds, fetches your available DPD product list and displays the product names and count.

Results are displayed directly on the page with a green tick (✓) for success or a red cross (✗) for failure.

### Common Errors

| Error | Likely Cause |
|---|---|
| `Authentication failed: No JWT Token received.` | Credentials are wrong or the DPD endpoint is unreachable. |
| `Failed to reach products endpoint` | Credentials are valid but your account has no products, or there is an API issue. |
| `Connection error. Please try again.` | Network problem between your server and DPD Connect. |

## Storage

Credentials are stored in the WordPress options table as the `dpdconnect_user_credentials` option. They are **not** encrypted at rest — ensure your WordPress database is secured appropriately.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
