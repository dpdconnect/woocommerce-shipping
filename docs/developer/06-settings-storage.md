<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Settings Storage

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Overview

All plugin settings are stored in the standard WordPress `wp_options` table. Each settings group is a single serialized array stored under one option key.

The `Option` class (`classes/Option.php`) provides a centralized, static interface to all settings values. **All code must read settings via `Option::*()` methods**, never via direct `get_option()` calls.

---

## Option Keys and Their Fields

### `dpdconnect_user_credentials`

Registered by `Settings\Credentials` (page: `dpdconnect_credentials`).

| Field key | Method | Description |
|---|---|---|
| `dpdconnect_connect_username` | `Option::connectUsername()` | DPD API username |
| `dpdconnect_connect_password` | `Option::connectPassword()` | DPD API password |

---

### `dpdconnect_general`

Registered by `Settings\General` (page: `dpdconnect_general`).

| Field key | Method | Values |
|---|---|---|
| `dpdconnect_account_type` | `Option::accountType()` | `b2c`, `b2b` |
| `dpdconnect_depot` | `Option::depot()` | Free text |
| `dpdconnect_label_format` | `Option::paperFormat()` | `A4`, `A6` |
| `dpdconnect_send_trackingemail` | `Option::sendTrackingEmail()` | `enabled`, `disabled` |
| `dpdconnect_download_format` | `Option::downloadFormat()` | `zip`, `pdf` |
| `dpdconnect_default_package_type` | `Option::defaultPackageType()` | `015010010`, `100050050` |

---

### `dpdconnect_company_info`

Registered by `Settings\Company` (page: `dpdconnect_company_info`).

| Field key | Method | Description |
|---|---|---|
| `dpdconnect_company_name` | `Option::companyName()` | Sender name on labels |
| `dpdconnect_company_address` | `Option::companyAddress()` | Sender street address |
| `dpdconnect_company_postal_code` | `Option::companyPostalCode()` | Sender postal code |
| `dpdconnect_company_city` | `Option::companyCity()` | Sender city |
| `dpdconnect_company_country_code` | `Option::companyCountryCode()` | ISO 2-letter code |
| `dpdconnect_company_phone` | `Option::companyPhone()` | Sender phone |
| `dpdconnect_company_email` | `Option::companyEmail()` | Sender email |
| `dpdconnect_vat_number` | `Option::vatNumber()` | VAT number |
| `dpdconnect_eori_number` | `Option::eoriNumber()` | EORI (dots stripped in use) |
| `dpdconnect_spr` | `Option::smallParcelReference()` | HMRC number |
| `dpdconnect_customs_terms` | `Option::customsTerms()` | `DAPDP`, `DAPNP` |

---

### `dpdconnect_products`

Registered by `Settings\Product` (page: `dpdconnect_products`).

| Field key | Method | Description |
|---|---|---|
| `dpdconnect_default_hs_code` | `Option::defaultHsCode()` | Fallback HS code |
| `dpdconnect_default_origin_country` | `Option::defaultOriginCountry()` | Fallback origin country |
| `dpdconnect_default_product_weight` | `Option::defaultProductWeight()` | Fallback weight in kg |

---

### `dpdconnect_parcelshop`

Registered by `Settings\Parcelshop` (page: `dpdconnect_parcelshop`).

| Field key | Method | Description |
|---|---|---|
| `dpdconnect_google_maps_api_key` | `Option::googleMapsApiKey()` | Own Google Maps key |
| `dpdconnect_use_dpd_google_maps_api_key` | `Option::useDpdGoogleMapsKey()` | `true`/`false` — use DPD's key |
| `dpdconnect_additional_parcelshop_methods` | `Option::additionalParcelshopMethods()` | Comma-separated method IDs |

`Option::additionalParcelshopMethods()` returns an array after splitting on commas and trimming whitespace.

---

### `dpdconnect_advanced`

Registered by `Settings\Advanced` (page: `dpdconnect_advanced`).

| Field key | Method | Description |
|---|---|---|
| `dpdconnect_connect_url` | `Option::connectUrl()` | Custom DPD API URL |
| `dpdconnect_callback_url` | `Option::callbackUrl()` | Custom callback base URL |
| `dpdconnect_async_treshold` | `Option::asyncTreshold()` | Max sync batch size (max 10) |
| `dpdconnect_auto_generate_shipping_label` | `Option::autoGenerateLabel()` | `1` or `null` |
| `dpdconnect_auto_generate_return_label` | `Option::autoGenerateReturnLabel()` | `1` or `null` |

`Option::asyncTreshold()` clamps the value to `MAX_ASYNC_TRESHOLD` (10) and defaults to 10 if not set.

---

## Shipping Method Instance Settings

Each DPD shipping method instance stores its own settings using WooCommerce's standard mechanism:

```
Option key: woocommerce_dpd_shipping_method_{instance_id}_settings
```

Fields:

| Key | Description |
|---|---|
| `zone_title` | Method title shown at checkout |
| `tax_status` | `taxable` or `none` |
| `cost` | Base cost expression |
| `dpd_method_type` | DPD product code |
| `dpd_checkout_description` | Description shown at checkout |
| `class_cost_{term_id}` | Per-shipping-class cost expression |
| `no_class_cost` | Cost for items with no shipping class |
| `type` | `class` or `order` calculation type |
| `show_from_day`, `show_from_time` | Saturday window start |
| `show_untill_day`, `show_untill_time` | Saturday window end |

---

## Order Meta

The plugin reads and writes these WooCommerce order meta fields:

| Meta key | Written by | Read by | Description |
|---|---|---|---|
| `_dpd_parcelshop_id` | `Pickup::storeParcelshopId()` | `OrderTransformer`, `LabelRequest` | Selected parcelshop ID |
| `dpd_tracking_numbers` | `LabelRequest`, `Callback` | `DownloadLabelBox` | DPD parcel numbers |

---

## Product Meta

| Meta key | Written by | Read by | Description |
|---|---|---|---|
| `dpd_hs_code` | `ShippingAttributes::save()` | `ProductInfo` | HS tariff code |
| `dpd_customs_value` | `ShippingAttributes::save()` | `ProductInfo` | Customs declared value |
| `dpd_origin_country` | `ShippingAttributes::save()` | `ProductInfo` | Country of origin |
| `dpd_age_check` | `ShippingAttributes::save()` | `OrderTransformer` | `yes` or not set |
| `dpd_shipping_product` | `ShippingAttributes::save()` | `FreshFreezeHelper`, `OrderTransformer` | `default`, `fresh`, `freeze` |
| `dpd_carrier_description` | `ShippingAttributes::save()` | `OrderTransformer` | Fresh/Freeze goods description |

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
