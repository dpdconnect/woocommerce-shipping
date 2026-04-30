<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Installation

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Requirements

| Requirement | Minimum version |
|---|---|
| WordPress | 5.8+ |
| WooCommerce | 6.0+ |
| PHP | 7.4+ |
| Composer | Required for dependency installation |

## Manual Installation

1. Download the plugin archive or clone the repository.
2. Run `composer install` in the plugin root to install PHP dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Upload the plugin folder to `wp-content/plugins/dpdconnect/`.
4. In WordPress admin, go to **Plugins → Installed Plugins**.
5. Click **Activate** next to **DPD Connect for WooCommerce**.

> **Note:** WooCommerce must be installed and activated before activating this plugin. If WooCommerce is not active, the plugin will load but no functionality will be registered.

## What Happens on Activation

When the plugin activates, it automatically creates three custom database tables:

- `wp_dpdconnect_labels` — stores generated shipping label PDFs
- `wp_dpdconnect_batches` — tracks async label batches
- `wp_dpdconnect_jobs` — tracks individual label jobs within a batch

These tables use the WordPress database prefix configured in `wp-config.php`.

## Verifying Installation

After activating the plugin, confirm it loaded correctly:

1. Go to **WooCommerce → DPD Settings** in the WordPress admin sidebar.
2. You should see the DPD settings menu with tabs: **Credentials**, **General**, **Company**, **Products**, **Parcelshop**, and **Advanced**.

If the menu does not appear, check that WooCommerce is active.

## HPOS Compatibility

The plugin declares compatibility with WooCommerce's High-Performance Order Storage (HPOS / Custom Order Tables). No additional configuration is required for HPOS.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
