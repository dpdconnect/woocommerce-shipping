<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# File Structure

<!-- AUTO-GENERATED:START - Do not edit manually -->

```
dpdconnect/
├── dpdconnect.php                   ← Plugin entry point
├── composer.json                    ← Composer dependencies
├── composer.lock
│
├── assets/
│   ├── css/
│   │   ├── dpdconnect.css           ← Admin styles
│   │   └── dpd_checkout.css         ← Checkout parcelshop bar styles
│   └── images/
│       ├── dpd_logo.png
│       ├── dpd_logo_bw.png
│       ├── icon-dpd.png
│       ├── icon-dpd.svg
│       └── pickup.png
│
├── languages/
│   ├── dpdconnect.pot               ← Translation template
│   ├── dpdconnect-nl_NL.po          ← Dutch translation source
│   └── dpdconnect-nl_NL.mo          ← Compiled Dutch translation
│
└── classes/                         ← All PHP source (PSR-4: DpdConnect\classes)
    │
    ├── Connect/                     ← DPD API wrappers
    │   ├── Cache.php                ← WordPress transient cache adapter
    │   ├── Connection.php           ← Base class: SDK client factory
    │   ├── Country.php              ← Country helper
    │   ├── Label.php                ← Fetches label PDF from DPD API
    │   ├── Product.php              ← Fetches/filters DPD products
    │   └── Shipment.php             ← Creates sync/async shipments
    │
    ├── Database/                    ← wpdb wrappers
    │   ├── Batch.php                ← CRUD for wp_dpdconnect_batches
    │   ├── Job.php                  ← CRUD for wp_dpdconnect_jobs
    │   └── Label.php                ← CRUD for wp_dpdconnect_labels
    │
    ├── Exceptions/
    │   ├── InvalidOrderException.php    ← Thrown when order data is invalid
    │   └── InvalidResponseException.php ← Thrown when DPD response is unparseable
    │
    ├── Handlers/                    ← WordPress hook registrations
    │   ├── Activate.php             ← Plugin activation + DB table creation
    │   ├── Assets.php               ← CSS/JS enqueue
    │   ├── Callback.php             ← Async DPD callback handler
    │   ├── Download.php             ← Serves PDF/ZIP label downloads
    │   ├── DownloadLabelBox.php     ← Meta box: download existing labels
    │   ├── GenerateLabelBox.php     ← Meta box: generate new labels
    │   ├── LabelRequest.php         ← Core label request logic (bulk/single/auto)
    │   ├── Notice.php               ← WP admin notices
    │   ├── OrderColumn.php          ← DPD column in orders list
    │   ├── OrderListActions.php     ← DPD bulk actions in orders list
    │   ├── Pickup.php               ← Parcelshop checkout bar + AJAX
    │   ├── SelectDefaultPackageType.php ← Package type selection helper
    │   ├── ShippingAttributes.php   ← Product-level DPD fields (Shipping tab)
    │   ├── ShippingMethods.php      ← Registers dpd_shipping_method
    │   ├── Translation.php          ← Loads plugin translations
    │   ├── Update.php               ← Plugin version migration handler
    │   └── trackingemail/
    │       ├── index.php            ← Tracking email HTML template
    │       ├── dpd-logo.png
    │       ├── dpd-logo@2x.png
    │       └── DPD_logo_redgrad_rgb_responsive.svg
    │
    ├── Pages/                       ← Admin page renderers
    │   ├── Batches.php              ← Batch overview page
    │   ├── FreshFreeze.php          ← Fresh/Freeze date input page
    │   └── Jobs.php                 ← Job overview page
    │
    ├── Service/
    │   └── SettingsDataValidator.php ← Validates settings before saving
    │
    ├── Settings/                    ← Settings page registrations
    │   ├── Advanced.php             ← Advanced settings tab
    │   ├── Company.php              ← Company info tab
    │   ├── Credentials.php          ← Credentials tab
    │   ├── General.php              ← General settings tab
    │   ├── Handler.php              ← Orchestrates all settings tabs
    │   ├── Menu.php                 ← Registers WP admin menu entries
    │   ├── Parcelshop.php           ← Parcelshop settings tab
    │   └── Product.php              ← Product defaults tab
    │
    ├── enums/                       ← Status constants
    │   ├── BatchStatus.php          ← Batch status values + HTML tags
    │   ├── JobStatus.php            ← Job status values + HTML tags
    │   ├── NoticeType.php           ← Admin notice types (error/info)
    │   └── ParcelType.php           ← Regular vs return parcel types
    │
    ├── producttypes/                ← DPD product type definitions
    │   ├── B2B.php
    │   ├── Fresh.php
    │   ├── Freeze.php
    │   ├── Parcelshop.php
    │   ├── Predict.php
    │   └── ProductTypeInterface.php
    │
    ├── shippingmethods/             ← WooCommerce shipping method
    │   ├── DPDShippingMethod.php    ← Extends WC_Shipping_Method
    │   └── includes/
    │       ├── class-wc-eval-math.php            ← Math expression evaluator
    │       └── settings-dpd-shipping-method.php  ← Instance settings definition
    │
    ├── BatchList.php                ← WP_List_Table for batches
    ├── FreshFreezeHelper.php        ← Fresh/Freeze order grouping + redirects
    ├── JobList.php                  ← WP_List_Table for jobs
    ├── JobView.php                  ← Single job detail view
    ├── Option.php                   ← Central settings accessor (all get_option calls)
    ├── OrderResponseTransformer.php ← Parses DPD error response details
    ├── OrderTransformer.php         ← Builds DPD shipment payload from WC order
    ├── OrderValidator.php           ← Validates order fields before label creation
    ├── ProductInfo.php              ← Reads product-level DPD attributes
    ├── Router.php                   ← URL-based routing (label download, single print)
    ├── TypeHelper.php               ← Product type detection utilities
    └── Version.php                  ← Plugin version constant
```

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
