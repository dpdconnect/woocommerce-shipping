<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Key Classes

<!-- AUTO-GENERATED:START - Do not edit manually -->

## `Option` — `classes/Option.php`

Central settings accessor. All reads from WordPress options for DPD settings go through this class.

**Constant:** `MAX_ASYNC_TRESHOLD = 10`

**Key methods:**

| Method | Returns | Notes |
|---|---|---|
| `accountType()` | `string\|null` | `b2c` or `b2b` |
| `depot()` | `string\|null` | 4-digit depot number |
| `paperFormat()` | `string\|null` | `A4` or `A6` |
| `sendTrackingEmail()` | `string\|null` | `enabled` or `disabled` |
| `downloadFormat()` | `string\|null` | `zip` or `pdf` |
| `defaultPackageType()` | `string\|null` | `015010010` or `100050050` |
| `connectUsername()` | `string\|null` | DPD API username |
| `connectPassword()` | `string\|null` | DPD API password |
| `companyName()` | `string\|null` | Sender name |
| `asyncTreshold()` | `int` | Max sync batch, clamped to 10 |
| `autoGenerateLabel()` | `mixed\|null` | `1` if enabled |
| `autoGenerateReturnLabel()` | `mixed\|null` | `1` if enabled |
| `additionalParcelshopMethods()` | `array` | Parsed method IDs |
| `callbackUrl()` | `string\|null` | Custom callback base URL |

---

## `OrderTransformer` — `classes/OrderTransformer.php`

Builds the DPD shipment request payload from a WooCommerce order.

**Constructor:** `__construct($validator)` — injects an `OrderValidator` instance.

**Key method:** `createShipment($orderId, $dpdProduct, $parcelCount, $orderItems, $shippingProduct, $freshFreezeData, $volume)`

What it does:
- Reads sender info from `Option`
- Reads receiver info from WC order
- Reads product DPD attributes from `ProductInfo`
- Converts weights to DPD units
- Adds parcelshop ID for parcelshop products
- Adds predict/parcelshop email notifications
- Adds age check flag
- Builds customs lines per order item
- Builds Fresh/Freeze parcels if applicable
- Validates the assembled shipment via `OrderValidator`

**Weight conversion:** Supports kg, g, lbs, oz → DPD decagram units (×100 for kg).

---

## `Connect\Connection` — `classes/Connect/Connection.php`

Base class for all DPD API connections. Builds and configures the DPD PHP SDK client.

- Reads credentials + URL from `Option`
- Caches JWT token in `wp_options` (`dpdconnect_jwt_token`)
- Registers a token update callback to persist refreshed tokens
- Attaches `Connect\Cache` (WordPress transient cache) to the SDK client

**Static method:** `getPublicJwtToken()` — obtains a public JWT token for the parcelshop map widget (no label creation scope).

---

## `Connect\Shipment` — `classes/Connect/Shipment.php`

Extends `Connection`. Handles shipment creation via the DPD API.

**Methods:**

- `create($shipments, $map, $type)` — synchronous shipment creation. Returns the API response. Stores each label in the database. Throws on error with WP notices.
- `createAsync($shipments, $map, $type)` — async shipment creation. Creates a batch + jobs. Returns the batch ID.

**Print options** (built internally):
```php
[
    'printerLanguage' => 'PDF',
    'paperFormat' => Option::paperFormat(),  // A4 or A6
    'verticalOffset' => 0,
    'horizontalOffset' => 0,
]
```

---

## `Connect\Product` — `classes/Connect/Product.php`

Fetches and filters the list of DPD products available on your account.

**Key methods:**

| Method | Description |
|---|---|
| `getList()` | Raw product list from DPD API |
| `getAllowedProducts()` | Filtered list (depends on account type B2C/B2B) |
| `getProductByCode($code)` | Returns single product array by code |
| `getAllowedProductsByType($type)` | Filtered by product type (e.g. parcelshop) |

---

## `TypeHelper` — `classes/TypeHelper.php`

> Note: Marked as temporary in the codebase — used until the DPD API supplies product types directly.

Static helpers for detecting DPD product characteristics:

| Method | Detection logic |
|---|---|
| `isParcelshop($product)` | `type` contains `parcelshop` |
| `isPredict($product)` | `type` contains `predict` |
| `isSaturday($product)` | `code` contains `6` |
| `isReturn($product)` | `name` contains `return` |
| `isHomeDelivery($product)` | Is predict OR is Saturday |
| `isFresh($product)` | `type` contains `fresh` |
| `isFreeze($product)` | `type` contains `freeze` |
| `convertServiceToCode($product)` | Maps code `6` and `AGE` → `B2C` |

---

## `Handlers\LabelRequest` — `classes/Handlers/LabelRequest.php`

The central label orchestration class. All label creation paths go through this class.

**Key methods:**

| Method | Called from | Description |
|---|---|---|
| `handle()` | `dpdconnect.php` | Registers WP hooks |
| `bulk($redirect_to, $action, $post_ids, $freshFreezeData)` | Bulk action filter | Processes multiple orders |
| `single($postID, $type, $parcelCount, $volume, $freshFreezeData)` | Meta box, auto-generate, bulk | Processes one order |
| `autoGenerateLabel($order_id)` | `woocommerce_order_status_processing` | Auto-generates on Processing status |
| `sendTrackingMail($emailData)` | After sync label creation | Sends tracking email via `wp_mail` |
| `redirect()` | On error | Redirects to orders list or previous page |

**Duplicate prevention:** Both `bulk()` and `single()` use static arrays keyed by md5 of input to prevent re-execution within the same request.

---

## `Handlers\Pickup` — `classes/Handlers/Pickup.php`

Manages the parcelshop checkout experience.

**Key methods:**

| Method | Hook | Description |
|---|---|---|
| `registerScripts()` | `wp_enqueue_scripts` | Loads DPDConnect.js from DPD CDN |
| `addColumn()` | `wp_head` | Injects parcelshop bar + initialization JS |
| `selectParcelShop()` | `wp_ajax_select_parcelshop` | Saves parcelshop ID to WC session via AJAX |
| `validateParcelshopSelection()` | `woocommerce_checkout_process` | Classic checkout validation |
| `storeParcelshopId($order, $data)` | Store API checkout update | Block checkout: validates + saves to order meta |

**Token generation:** A public JWT token is generated server-side on each checkout page load and embedded in the JavaScript. Credentials are never sent to the frontend.

---

## `Service\SettingsDataValidator` — `classes/Service/SettingsDataValidator.php`

Validates plugin settings and displays inline errors on settings pages.

**Validation groups:**
- `validateCredentialSettings()` — username, password, and live authentication test
- `validateGeneralSettings()` — depot number (must be exactly 4 characters)
- `validateCompanySettings()` — all company fields (length, format, email regex)
- `validateProductSettings()` — HS code (max 10 chars), origin country (max 2 chars)

Errors are rendered as `<ul>` lists with red text directly inside the settings section callback.

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
