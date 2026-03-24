<div align="center">

<img src="assets/images/icon-dpd.svg" width="120" alt="DPD Connect" />

# DPD Connect for WooCommerce

![PHP](https://img.shields.io/badge/PHP-8.0%2B-8892BF?logo=php&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0%2B-96588A?logo=woocommerce&logoColor=white)
![License](https://img.shields.io/badge/License-GPL--3.0-darkgreen)
![Version](https://img.shields.io/badge/Version-2.0.2-brightgreen)

**Integrate DPD parcel shipping directly into your WooCommerce store.**
Generate labels, offer Parcelshop pickup at checkout, and monitor async batches — all from the WooCommerce admin.

[Features](#-features) · [Requirements](#-requirements) · [Installation](#-installation) · [Configuration](#-configuration) · [Development](#-development)

</div>

---

## 📦 Features

### 🏷️ Label Generation

| Feature | Description |
|---|---|
| **Single label** | Generate from any order page via the DPD Connect meta box |
| **Bulk labels** | Select multiple orders from the orders list and process in one action |
| **Return labels** | Generated and stored separately from outbound shipping labels |
| **Fresh & Freeze** | Temperature-controlled shipments with per-product shipping type assignment |
| **Multi-parcel** | Split a single order across multiple parcels via the parcel count input |

### 🗺️ Delivery Options

| Feature | Description |
|---|---|
| **Parcelshop pickup** | Interactive map widget at checkout — customers choose a nearby DPD parcel shop |
| **Predict** | DPD Predict delivery with email notification to the recipient |
| **Saturday delivery** | Configurable day/time window — automatically hidden outside the booking window |
| **B2B delivery** | Commercial address delivery type for business-to-business shipments |
| **Age check** | Flag 18+ products for age verification on delivery |

### ⚡ Async Processing

| Feature | Description |
|---|---|
| **Sync** | Orders up to the async threshold (default 10) are processed immediately with instant PDF download |
| **Async batches** | Larger selections are queued; DPD processes them and calls back when done |
| **Batch overview** | Monitor batch progress with shipment count, success/failure counts, and status |
| **Job overview** | Inspect individual job status, DPD external IDs, error messages, and linked labels |

### 🔔 Notifications

| Feature | Description |
|---|---|
| **Tracking email** | DPD-branded HTML email sent to the customer after each successful label creation |
| **Auto-generate on Processing** | Optionally create a shipping label automatically when payment is confirmed |
| **Auto-generate return label** | Optionally create a return label alongside every outbound shipping label |

---

## ✅ Requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.0+ |
| WordPress | 5.8+ |
| WooCommerce | 6.0+ |
| Composer | Required for dependency installation |
| DPD Connect account | Required — obtain credentials from DPD |

---

## 🚀 Installation

```bash
# 1. Clone or download the plugin
git clone https://github.com/dpdconnect/woocommerce-shipping.git

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Upload to WordPress plugins directory
cp -r woocommerce-shipping /path/to/wordpress/wp-content/plugins/dpdconnect
```

Then activate the plugin in **WordPress Admin → Plugins → Installed Plugins**.

> WooCommerce must be installed and active before activating this plugin.

---

## ⚙️ Configuration

Navigate to **WooCommerce → DPD Settings** after activation.

### Quick Setup Checklist

- [ ] **Credentials** — enter your DPD Connect username and password, then click *Check Credentials* to verify
- [ ] **General** — set account type (B2C/B2B), depot number, label format (A4/A6), and download format
- [ ] **Company** — fill in your sender address — this appears on every label
- [ ] **Products** — set fallback HS code, country of origin, and product weight for customs
- [ ] **Parcelshop** — configure Google Maps API key for the checkout map widget
- [ ] **Shipping Zones** — add *DPD Shipping Method* to your WooCommerce shipping zones and select a DPD product per zone

### Shipping Method Setup

1. **WooCommerce → Settings → Shipping → Shipping Zones → Edit Zone**
2. Click **Add shipping method** → select **DPD Shipping Method**
3. Click **Edit** on the new method and select the DPD product type (Standard, Parcelshop, Predict, Saturday, etc.)
4. Set the shipping cost using flat amounts or dynamic expressions (`[qty]`, `[cost]`, `[fee percent="x"]`)

---

## 🛠️ Development

### Project Structure

```
dpdconnect/
├── dpdconnect.php          # Plugin entry point
├── composer.json
├── assets/                 # CSS, images
├── languages/              # Translations (.pot, .po, .mo)
└── classes/
    ├── Connect/            # DPD API wrappers (SDK integration)
    ├── Database/           # wpdb repositories (labels, batches, jobs)
    ├── Handlers/           # WordPress hook registrations
    ├── Pages/              # Admin page renderers
    ├── Settings/           # Settings page registrations
    ├── Service/            # Validators
    ├── enums/              # Status constants
    ├── producttypes/       # DPD product type definitions
    ├── shippingmethods/    # WC_Shipping_Method extension
    └── Option.php          # Central settings accessor
```

### Database Tables

The plugin creates three custom tables on activation:

| Table | Purpose |
|---|---|
| `{prefix}dpdconnect_labels` | Stores generated label PDFs as binary blobs |
| `{prefix}dpdconnect_batches` | Tracks async batch requests |
| `{prefix}dpdconnect_jobs` | Tracks individual shipment jobs within a batch |

### Key Classes

| Class | Description |
|---|---|
| `Option` | Central accessor for all plugin settings (wraps `get_option`) |
| `OrderTransformer` | Builds DPD shipment payload from a WooCommerce order |
| `Connect\Shipment` | Sends sync/async shipment requests to the DPD API |
| `Handlers\LabelRequest` | Orchestrates all label creation paths (bulk, single, auto) |
| `Handlers\Pickup` | Manages the parcelshop checkout bar and AJAX handlers |
| `Handlers\Callback` | Processes async DPD callbacks (`admin-post.php?action=dpdbatch`) |

### Full Documentation

Comprehensive user and developer documentation is available in the [`docs/`](docs/README.md) directory:

- [Architecture](docs/developer/01-architecture.md)
- [Label Creation Flow](docs/developer/03-label-creation-flow.md)
- [Database Schema](docs/developer/04-database-schema.md)
- [WordPress Hooks](docs/developer/05-wordpress-hooks.md)
- [Debugging](docs/developer/07-debugging.md)

---

## 📄 License

GPL-3.0-or-later — see [LICENSE](LICENSE) for details.

**Author:** DPD / [X-Interactive.nl](https://github.com/dpdconnect)
