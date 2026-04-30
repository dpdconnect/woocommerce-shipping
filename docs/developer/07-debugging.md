<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Debugging

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Enabling WordPress Debug Mode

Add these to `wp-config.php` to capture PHP errors and warnings:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);   // logs to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false); // hides from frontend
```

---

## Settings Validation Errors

The Credentials, General, Company, and Products settings pages run live validation and display errors at the top of each settings section. These errors come from `Service\SettingsDataValidator`.

**Field length limits enforced:**

| Field | Max length | Min length |
|---|---|---|
| Depot | 4 | 4 |
| Company name | 35 | — |
| Company address | 40 | — |
| Postal code | 9 | — |
| City | 35 | — |
| Country code | 2 | — |
| Email | 50 | — |
| VAT number | 20 | — |
| HS code | 10 | — |
| Origin country | 2 | — |
| Username | 100 | — |
| Password | 3000 | — |

---

## Checking Credentials Programmatically

Use the **Check Credentials** button on the Credentials page to test authentication + product endpoint without saving. The result shows both checks with success/failure status.

To test in PHP:

```php
$jwtToken = \DpdConnect\classes\Connect\Connection::getPublicJwtToken();
// Returns the JWT token string, or throws AuthenticateException / HttpException / ServerException
```

The JWT token is cached in `wp_options` as `dpdconnect_jwt_token` and refreshed automatically when it expires.

---

## Label Generation Errors

Label creation errors appear as WordPress admin notices. They are stored using `Handlers\Notice::add()` which calls `wc_add_notice()` or queues them for the next page load.

**Common errors and causes:**

| Error message | Cause | Fix |
|---|---|---|
| `Order has no shipping method` | Order was created without a shipping method | Check order edit page, assign a shipping method |
| `Shipping method has no DPD type` | Non-DPD shipping method used without Additional Parcelshop Method config | Configure method in Parcelshop settings, or use a DPD method |
| `DPD Product could not be found` | Bulk action product code not in your account | Check your account products via Credentials → Check Credentials |
| `Please select a parcelshop. No parcelshop was selected during checkout` | Order used parcelshop method but `_dpd_parcelshop_id` meta is missing | Customer did not select a parcelshop; create label manually with a different product type |
| `No ParcelShop shipping method was used for this order` | Parcelshop-type product selected but order has no parcelshop ID | Same as above |
| `Order X: [field] [message]` | DPD API validation error on a specific field | Check the field in the order (e.g. missing address, invalid postal code) |
| `Authentication failed` | Invalid credentials or DPD endpoint unavailable | Check credentials, verify DPD Connect is reachable |

---

## Async Job Failures

Failed async jobs appear on the **Jobs** page with a status of **Failed** and an error message. To investigate:

1. Go to **WooCommerce → DPD Settings → Jobs**.
2. Find the failed job; check the **Error** and **State message** columns.
3. The error is stored as serialized PHP in `dpdconnect_jobs.error`.

### Callback Not Received

If jobs stay in **Queued** status indefinitely:

1. Check the **Callback URL** in Advanced Settings is publicly accessible.
2. Test with: `curl -X POST https://yoursite.com/wp-admin/admin-post.php?action=dpdbatch`
3. Verify no WAF/firewall is blocking POST requests to the admin URL.
4. Check DPD Connect support to confirm the callback URL is registered.

---

## Parcelshop Not Showing at Checkout

1. Confirm at least one DPD Parcelshop shipping method is configured and available for the customer's location.
2. Check browser console for JavaScript errors — look for `DPD Parcelshop:` prefixed log messages.
3. If the DPDConnect library fails to load, check for ad-blockers or network access issues to the DPD CDN.
4. Verify the Google Maps API key is valid and the Maps JavaScript API is enabled for it.
5. Check the `additionalParcelshopMethods` setting if using third-party shipping methods.

---

## JWT Token Cache

The SDK caches JWT tokens using `Connect\Cache`, which wraps WordPress transients. If you suspect a stale token:

```php
// Delete cached token
delete_option('dpdconnect_jwt_token');
```

Cached DPD data (e.g. product list) is stored as WordPress transients prefixed `dpd_`. Clear with:

```php
// Delete all DPD transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dpd_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dpd_%'");
```

---

## Database Inspection

To view stored labels:

```sql
SELECT id, order_id, type, shipment_identifier, parcel_numbers, created_at
FROM wp_dpdconnect_labels
ORDER BY created_at DESC
LIMIT 20;
```

To view batch/job status:

```sql
SELECT b.id, b.status, b.shipment_count, b.success_count, b.failure_count,
       j.order_id, j.status as job_status, j.error, j.state_message
FROM wp_dpdconnect_batches b
JOIN wp_dpdconnect_jobs j ON j.batch_id = b.id
ORDER BY b.created_at DESC;
```

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
