<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# WordPress Hooks

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Actions Registered by the Plugin

| Hook | Handler | Method | Notes |
|---|---|---|---|
| `plugins_loaded` | `Translation` | `handle` | Loads plugin text domain |
| `before_woocommerce_init` | _(anonymous)_ | — | Declares HPOS compatibility |
| `admin_init` | `Settings\General` | `render` | Registers general settings fields |
| `admin_init` | `Settings\Credentials` | `render` | Registers credentials fields |
| `admin_init` | `Settings\Company` | `render` | Registers company info fields |
| `admin_init` | `Settings\Product` | `render` | Registers product default fields |
| `admin_init` | `Settings\Parcelshop` | `render` | Registers parcelshop settings fields |
| `admin_init` | `Settings\Advanced` | `render` | Registers advanced settings fields |
| `wp_enqueue_scripts` | `Pickup` | `registerScripts` | Loads DPDConnect JS on checkout |
| `wp_head` | `Pickup` | `addColumn` | Injects parcelshop bar + JS on checkout |
| `wp_ajax_select_parcelshop` | `Pickup` | `selectParcelshop` | Saves parcelshop to session (logged in) |
| `wp_ajax_nopriv_select_parcelshop` | `Pickup` | `selectParcelshop` | Saves parcelshop to session (guests) |
| `woocommerce_checkout_process` | `Pickup` | `validateParcelshopSelection` | Blocks classic checkout if no parcelshop |
| `woocommerce_store_api_checkout_update_order_from_request` | `Pickup` | `storeParcelshopId` | Validates + stores parcelshop for block checkout |
| `woocommerce_order_status_processing` | `LabelRequest` | `autoGenerateLabel` | Auto-generates label on payment |
| `admin_post_nopriv_dpdbatch` | `Callback` | `listen` | Receives async DPD callbacks |
| `woocommerce_update_order` | `GenerateLabelBox` | `process` | Processes label generation from meta box |
| `add_meta_boxes` | `GenerateLabelBox` | `add` | Adds Generate Labels meta box |
| `add_meta_boxes` | `DownloadLabelBox` | `add` | Adds Download Labels meta box |
| `woocommerce_product_options_shipping` | `ShippingAttributes` | `add` | Adds DPD fields to product Shipping tab |
| `woocommerce_process_product_meta_simple` | `ShippingAttributes` | `save` | Saves DPD product attributes |
| `wp_ajax_dpdconnect_check_credentials` | `Settings\Credentials` | `ajaxCheckCredentials` | Credential check AJAX handler |

## Filters Registered by the Plugin

| Hook | Handler | Method | Priority | Notes |
|---|---|---|---|---|
| `handle_bulk_actions-edit-shop_order` | `LabelRequest` | `bulk` | 10 | Old WooCommerce order list |
| `handle_bulk_actions-woocommerce_page_wc-orders` | `LabelRequest` | `bulk` | 10 | New WooCommerce HPOS order list |
| `woocommerce_shipping_methods` | `ShippingMethods` | _(anonymous)_ | — | Registers `dpd_shipping_method` |
| `woocommerce_available_shipping_methods` | `DPDShippingMethod` | `hide` | — | Hides Saturday method outside time window |

## WooCommerce Hooks Used for Settings Menu

Registered by `Settings\Menu`:

| Hook | Action |
|---|---|
| `admin_menu` | Adds **DPD Settings** submenu under WooCommerce |
| `admin_menu` | Adds **Batches** submenu page |
| `admin_menu` | Adds **Jobs** submenu page |

## Plugin Lifecycle Hooks

| Hook | Handler | Notes |
|---|---|---|
| `register_activation_hook` | `Activate` | Registers `dpdconnect_activate` function |
| `woocommerce_update_options_shipping_{id}` | `DPDShippingMethod` | Saves shipping method instance settings |

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
