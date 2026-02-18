<div align="center">

<img src="assets/images/icon-dpd.svg" alt="DPD Connect for WooCommerce" width="120" />

# DPD Connect for WooCommerce

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-8892be?logo=php&logoColor=white)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a?logo=woocommerce&logoColor=white)](https://woocommerce.com/)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Version](https://img.shields.io/badge/Version-2.0.2-red)](https://github.com/dpdconnect/woocommerce-shipping/releases)
[![HPOS](https://img.shields.io/badge/HPOS-compatible-brightgreen)](https://woocommerce.com/document/high-performance-order-storage/)

**Integrate DPD parcel shipping directly into your WooCommerce store.**
Generate labels, offer Parcelshop pickup at checkout, and monitor async batches — all from the WordPress admin.

[Features](#-features) · [Requirements](#-requirements) · [Installation](#-installation) · [Configuration](#-configuration) · [Development](#-development) · [License](#-license)

</div>

---

## 📦 Features

### 🏷️ Label Generation

| Feature | Description |
|---|---|
| **Single label** | Generate from any order page via the sidebar meta box |
| **Bulk labels** | Select multiple orders and process them in one action |
| **Return labels** | Generated and stored separately from shipping labels |
| **Fresh & Freeze** | Temperature-controlled shipments with per-parcel expiration dates |
| **Multi-parcel** | Split a single order across multiple parcels with even weight distribution |

### 🗺️ Delivery Options

| Feature | Description |
|---|---|
| **DPD Parcelshop** | Embedded map picker at checkout (classic & block checkout) |
| **Saturday delivery** | Methods automatically hidden outside configured time windows |
| **Age check** | Flag products to require recipient age verification at delivery |
| **B2C and B2B** | Display the correct DPD product set based on your contract type |

### ⚡ Processing & Downloads

| Feature | Description |
|---|---|
| **Synchronous** | Small batches generated and downloaded immediately |
| **Asynchronous** | Larger batches queued on DPD; labels arrive via webhook |
| **A4 and A6** | Choose the format that matches your printer |
| **ZIP or merged PDF** | Download bulk labels as a ZIP archive or a single merged PDF |

### 🌍 Customs & International

- Per-product **HS code**, **country of origin**, and **customs value** sent with every international shipment
- Default country of origin configurable at store level as a fallback

### 🔔 Notifications & Monitoring

- **Tracking email** — optional HTML email to the customer with barcode(s) and DPD tracking link
- **Batch & job monitor** — dedicated admin pages show real-time async batch progress and per-job status

---

## ✅ Requirements

### Server

| Requirement | Minimum |
|---|---|
| PHP | **8.0** |
| WordPress | **6.0+** |
| WooCommerce | **7.0+** |
| MySQL / MariaDB | **5.7+** / **10.3+** |

### DPD Account

| Requirement | Notes |
|---|---|
| DPD shipping contract | Required for all functionality |
| API username & password | Provided by DPD on account creation |
| Depot code | Provided by DPD, e.g. `0522` |
| Public callback URL | Required only for **async** label creation |

> **Google Maps API key** — the plugin ships with a DPD-provided key for the Parcelshop map. No key of your own is needed to get started. High-volume stores are recommended to supply their own key via **Settings → Parcelshop** to avoid rate limiting.

---

## 🚀 Installation

### Option A — Upload via WordPress admin

1. Download the plugin ZIP
2. Go to **WP Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. Click **Activate Plugin**

### Option B — Manual (FTP / file copy)

1. Copy the `dpdconnect/` folder into `wp-content/plugins/`
2. Go to **WP Admin → Plugins** and activate **DPD Connect for WooCommerce**

On first activation, the plugin automatically creates three database tables:

| Table | Purpose |
|---|---|
| `wp_dpdconnect_labels` | Stores generated label PDFs |
| `wp_dpdconnect_batches` | Tracks async batch requests |
| `wp_dpdconnect_jobs` | Tracks individual jobs within a batch |

---

## ⚙️ Configuration

Navigate to **WP Admin → DPD Connect → Settings** and fill in each tab in order.

<details>
<summary><strong>1. 🔑 Credentials</strong></summary>
<br>

Enter the **DPD API username** and **password** provided by DPD when your account was created.

</details>

<details>
<summary><strong>2. ⚙️ General</strong></summary>
<br>

| Setting | Description |
|---|---|
| Account type | B2C (consumers) or B2B (businesses) |
| Depot code | Your DPD depot number, e.g. `0522` |
| Label format | A4 (4 per sheet) or A6 (thermal label size) |
| Tracking email | Enable to automatically notify customers after label creation |
| Download format | ZIP archive or merged PDF for bulk downloads |
| Default package type | Small Parcel (15×10×10 cm) or Normal Parcel (100×50×50 cm) |

</details>

<details>
<summary><strong>3. 🏢 Company</strong></summary>
<br>

Sender details printed on every label and used as the `From:` address in tracking emails: company name, address, phone, and email.

</details>

<details>
<summary><strong>4. 📦 Product</strong></summary>
<br>

Store-wide defaults used when a product has no individual value set:

- **Default country of origin** — ISO 3166-1 alpha-2 code (e.g. `NL`)
- **Default product weight** — in kilograms

</details>

<details>
<summary><strong>5. 🗺️ Parcelshop</strong></summary>
<br>

| Setting | Description |
|---|---|
| Use DPD's API key | **Enabled by default.** Uses DPD's shared Google Maps key — no setup required. |
| Google Maps API key | Your own key (Maps JavaScript API + Places API). Leave empty to use DPD's key. |
| Additional parcelshop methods | Enable parcelshop for non-DPD shipping methods (e.g. Table Rate Shipping) |

</details>

<details>
<summary><strong>6. 🔧 Advanced</strong></summary>
<br>

| Setting | When to use |
|---|---|
| Connect URL | Override the DPD API endpoint (e.g. for staging) |
| Callback URL | Override the URL DPD calls for async labels (e.g. local dev via ngrok) |
| Async threshold | Orders above this count switch from sync to async (max 10) |

</details>

After saving settings, add a DPD shipping method under **WooCommerce → Settings → Shipping → Shipping zones**.

---

## 🛠️ Development

### Prerequisites

- PHP 8.0+
- [Composer](https://getcomposer.org/)

### Setup

```bash
git clone https://github.com/dpdconnect/woocommerce-shipping.git
cd woocommerce-shipping
composer install
```

### Dependencies

| Package | Version | Purpose |
|---|---|---|
| [`dpdconnect/php-sdk`](https://github.com/dpdconnect/php-sdk) | `^1.1` | DPD REST API client — JWT auth, shipment creation, label download |
| [`myokyawhtun/pdfmerger`](https://github.com/myokyawhtun/PDFMerger) | `dev-master` | Merges multiple label PDFs into a single bulk download |

### Directory structure

```
dpdconnect/
├── dpdconnect.php              Entry point — bootstraps all handlers
├── composer.json
├── assets/                     CSS for admin and checkout
├── classes/
│   ├── Connect/                DPD API wrappers (Connection, Shipment, Label, …)
│   ├── Database/               Custom table repositories (Label, Batch, Job)
│   ├── Handlers/               WordPress hook registrations
│   │   └── trackingemail/      HTML email template + DPD logo assets
│   ├── Pages/                  Admin list table pages (Batches, Jobs, FreshFreeze)
│   ├── Settings/               WooCommerce settings page handlers
│   ├── enums/                  PHP enums (ParcelType, BatchStatus, JobStatus, …)
│   └── producttypes/           DPD product abstractions (Predict, Parcelshop, B2B, Fresh)
├── languages/                  Translation files (.po / .mo)
└── vendor/                     Composer dependencies
```

### Data flow — label generation

```
LabelRequest (bulk action hook)
  └── OrderTransformer::createShipment()   WC order → DPD shipment array
        └── OrderValidator                 Validates receiver + product data
              └── Connect\Shipment         Submits to DPD API
                    └── Job + Batch        Stored in custom DB tables
                          └── Callback     Webhook receives PDF, stores in Label table
                                └── Router Labels served via ?plugin=dpdconnect&file=shipping_label
```

---

## 📄 License

Released under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

---

<div align="center">

Built with ❤️ by [X-Interactive](https://x-interactive.nl) &nbsp;·&nbsp; [DPD Connect portal](https://integrations.dpd.nl/)

</div>
