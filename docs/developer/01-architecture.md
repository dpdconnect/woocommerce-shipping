<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Architecture

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Overview

DPD Connect for WooCommerce is a standard WordPress plugin that integrates with the DPD Connect REST API via the `dpdconnect/php-sdk` Composer package.

The plugin follows a **handler-based architecture**: each area of functionality is encapsulated in a static handler class that registers WordPress action/filter hooks during plugin initialization. There is no persistent runtime state — all processing happens within request scope.

---

## Plugin Initialization

```mermaid
flowchart TD
    WP[WordPress boot] --> Entry[dpdconnect.php]
    Entry --> Activate[Activate::handle\nCreate DB tables]
    Entry --> Update[Update::handle\nRun migrations]
    Entry --> WC{WooCommerce\nactive?}

    WC -- No --> Stop([Plugin inactive])
    WC -- Yes --> Handlers

    subgraph Handlers[Handler Registration]
        H1[Settings\\Handler\nAdmin settings pages]
        H2[Assets::handle\nCSS / JS]
        H3[GenerateLabelBox\nDownloadLabelBox\nOrder meta boxes]
        H4[Pickup::handle\nParcelshop checkout]
        H5[ShippingMethods\nRegister WC method]
        H6[LabelRequest\nBulk actions + auto-generate]
        H7[Callback::handle\nAsync DPD callbacks]
        H8[OrderColumn\nOrderListActions]
        H9[Router::init\nURL routing]
    end

    Handlers --> H1 & H2 & H3 & H4 & H5 & H6 & H7 & H8 & H9
```

---

## Sync Label Creation Flow

```mermaid
sequenceDiagram
    participant Admin
    participant LabelRequest
    participant OrderTransformer
    participant DPD_SDK as DPD PHP SDK
    participant DB as Database\\Label
    participant Browser

    Admin->>LabelRequest: bulk() / single()
    LabelRequest->>OrderTransformer: createShipment(orderId, product, ...)
    OrderTransformer->>OrderTransformer: Read WC order + Option settings
    OrderTransformer->>OrderTransformer: Build shipment payload
    OrderTransformer-->>LabelRequest: shipment[]

    LabelRequest->>DPD_SDK: Shipment::create(shipments)
    DPD_SDK->>DPD_SDK: POST /shipment
    DPD_SDK-->>LabelRequest: labelResponses[]

    loop Each label response
        LabelRequest->>DB: Label::create(orderId, pdf, type, ...)
    end

    opt Tracking email enabled
        LabelRequest->>Admin: sendTrackingMail()
    end

    LabelRequest->>Browser: Download::pdf() / zip() / mergedPdf()
```

---

## Async Label Creation Flow

```mermaid
sequenceDiagram
    participant Admin
    participant LabelRequest
    participant DPD_SDK as DPD PHP SDK
    participant DB_Batch as Database\\Batch
    participant DB_Job as Database\\Job
    participant DPD_Server as DPD Server
    participant Callback

    Admin->>LabelRequest: bulk() [> async threshold]
    LabelRequest->>DPD_SDK: Shipment::createAsync(shipments)
    DPD_SDK->>DPD_Server: POST /shipment/async + callbackUrl
    DPD_Server-->>DPD_SDK: jobId[] per shipment
    DPD_SDK-->>LabelRequest: response[]

    LabelRequest->>DB_Batch: Batch::create() → batchId
    loop Each job
        LabelRequest->>DB_Job: Job::create(batchId, jobId, orderId)
    end

    LabelRequest->>Admin: Redirect → Jobs overview page

    Note over DPD_Server,Callback: DPD processes labels asynchronously

    DPD_Server->>Callback: POST admin-post.php?action=dpdbatch
    Callback->>DB_Job: getByExternalId(jobId)

    alt Success (state = 4)
        Callback->>DPD_SDK: Label::get(parcelNumber)
        Callback->>DB_Batch: Label::create(orderId, pdf, ...)
        Callback->>DB_Job: updateStatus(SUCCESS, labelId)
        Callback->>DB_Batch: Batch::updateStatus()
        opt Tracking email enabled
            Callback->>Callback: sendMail(order, shipment)
        end
    else Failure (state ≥ 8)
        Callback->>DB_Job: updateStatus(FAILED, errors)
        Callback->>DB_Batch: Batch::updateStatus()
    end
```

---

## Parcelshop Checkout Flow

```mermaid
sequenceDiagram
    participant Customer
    participant Browser as Browser (JS)
    participant WP as WordPress AJAX
    participant Session as WC Session
    participant Order as WC Order

    Customer->>Browser: Loads checkout page
    Browser->>Browser: Pickup::addColumn() injects bar + JS
    Browser->>Browser: DPDConnect.js loaded from DPD CDN

    Customer->>Browser: Selects parcelshop shipping method
    Browser->>Browser: isParcelshopMethodSelected() → true
    Browser->>Browser: Show DPD bottom bar

    Customer->>Browser: Clicks "Select DPD Parcel Shop"
    Browser->>Browser: Open map modal
    Browser->>Browser: DPDConnect.show(token, address, lang)

    Customer->>Browser: Clicks parcel shop on map
    Browser->>WP: AJAX: action=select_parcelshop\n+ parcelshopId + nonce
    WP->>Session: Set dpd_order_metadata.parcelshop_id
    WP-->>Browser: success

    Browser->>Browser: Update bar with selected shop info

    Customer->>Browser: Places order
    alt Classic checkout
        Browser->>WP: woocommerce_checkout_process
        WP->>Session: validateParcelshopSelection()
    else Block checkout
        Browser->>WP: store_api_checkout_update_order_from_request
        WP->>Session: storeParcelshopId()
    end

    WP->>Order: update_meta_data('_dpd_parcelshop_id', id)
    WP->>Session: __unset('dpd_order_metadata')
```

---

## External Dependencies

| Package | Purpose |
|---|---|
| `dpdconnect/php-sdk` | DPD Connect REST API client |
| `myokyawhtun/pdfmerger` | Merging multiple PDFs into one file |

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
