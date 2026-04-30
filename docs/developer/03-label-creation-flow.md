<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Label Creation Flow

<!-- AUTO-GENERATED:START - Do not edit manually -->

## Entry Points

There are three triggers that initiate label creation:

```mermaid
flowchart LR
    A1[Admin bulk action\non order list] --> LR[LabelRequest::bulk]
    A2[Generate Labels\nmeta box on order page] --> LR2[LabelRequest::single]
    A3[Order status changes\nto Processing] --> LR3[LabelRequest::autoGenerateLabel]

    LR --> Core[Core label flow]
    LR2 --> Core
    LR3 --> Core
```

---

## Complete Label Decision Flow

```mermaid
flowchart TD
    Start([Label request]) --> FF{Order contains\nFresh/Freeze items?}

    FF -- Yes, no dates yet --> Redirect[Redirect to\nFresh/Freeze date form]
    FF -- No / dates provided --> Group[Group order items\nby shipping product]

    Group --> Loop[For each shipping\nproduct group]

    Loop --> Validate[OrderValidator\nvalidateReceiver + validateProduct]
    Validate --> Valid{Valid?}
    Valid -- No --> Error[Notice::add error\nRedirect to orders list]
    Valid -- Yes --> Transform[OrderTransformer::createShipment\nBuild shipment payload]

    Transform --> ProductType{Product type?}
    ProductType -- Fresh/Freeze --> FFParcels[createFreshFreezeParcels\nwith expiry dates per item]
    ProductType -- Parcelshop --> PSCheck{Parcelshop ID\nin order meta?}
    PSCheck -- No --> Error
    PSCheck -- Yes --> AddPS[Add parcelshopId\n+ notifications]
    ProductType -- Predict/Saturday --> AddNotify[Add predict\nEMAIL notification]
    ProductType -- Standard/B2B --> BuildParcels[Build standard parcels\nwith weight + volume]

    FFParcels --> Customs[addCustomsToShipment\nHS codes, origin, values]
    AddPS --> Customs
    AddNotify --> Customs
    BuildParcels --> Customs

    Customs --> Threshold{Count ≤\nasync threshold?}

    Threshold -- Yes\nSync --> Sync[Shipment::create\nPOST /shipment]
    Threshold -- No\nAsync --> Async[Shipment::createAsync\nPOST /shipment/async]

    Sync --> SyncResp[labelResponses[]]
    SyncResp --> StorePDF[Database\\Label::create\nStore PDF blob per label]
    StorePDF --> TrackEmail{Tracking email\nenabled?}
    TrackEmail -- Yes --> SendEmail[sendTrackingMail]
    TrackEmail -- No --> Download
    SendEmail --> Download[Download::pdf / zip / mergedPdf]

    Async --> CreateBatch[Database\\Batch::create]
    CreateBatch --> CreateJobs[Database\\Job::create\nper shipment]
    CreateJobs --> RedirectJobs[Redirect → Jobs overview]
```

---

## Weight Calculation

```mermaid
flowchart LR
    WI[WC product weights\nper order item × qty] --> Sum[Sum all item weights]
    Sum --> Zero{Total = 0?}
    Zero -- Yes --> DefaultW[Use default weight\nfrom Option::defaultProductWeight\n× 100]
    Zero -- No --> Convert[Convert to DPD units]

    Convert --> Unit{WC weight unit}
    Unit -- kg --> KG[× 100]
    Unit -- g --> G[÷ 10]
    Unit -- lbs --> LBS[× 45.359237]
    Unit -- oz --> OZ[× 2.834952313]

    KG & G & LBS & OZ --> DPDWeight[DPD weight\nin decagrams]
    DefaultW --> DPDWeight

    DPDWeight --> Split[Divide by parcel count\nceil per parcel]
```

---

## Product Resolution

How the plugin determines which DPD product to use for a label:

```mermaid
flowchart TD
    ActionType{Bulk action type?}

    ActionType -- Generic\ndpdconnect_create_labels_bulk_action --> ReadMethod[Read shipping method\nfrom WC order]
    ReadMethod --> HasDPDType{Method has\ndpd_method_type setting?}

    HasDPDType -- Yes --> GetByCode[Product::getProductByCode\nfrom method settings]
    HasDPDType -- No --> CheckAdditional{Is it an additional\nparcelshop method?}

    CheckAdditional -- Yes --> HasPSID{Order has\n_dpd_parcelshop_id?}
    HasPSID -- No --> ErrorPS[Error: no parcelshop selected]
    HasPSID -- Yes --> PSProduct[Get Parcelshop product]

    CheckAdditional -- No --> ErrorType[Error: no DPD type]

    ActionType -- Specific\ndpdconnect_create_CODE_labels --> ParseCode[Extract CODE\nfrom action string]
    ParseCode --> GetByCode2[Product::getProductByCode]
    GetByCode2 --> IsPS{Product type\nis parcelshop?}
    IsPS -- Yes --> HasPSID2{Order has\n_dpd_parcelshop_id?}
    HasPSID2 -- No --> ErrorPS2[Error: no parcelshop used]
    HasPSID2 -- Yes --> OK
    IsPS -- No --> OK([Use product])

    GetByCode --> OK
    PSProduct --> OK
    GetByCode2 --> OK
```

---

## Duplicate Prevention

Both `bulk()` and `single()` guard against being called twice in the same request (WooCommerce may fire both old and new HPOS bulk action hooks simultaneously):

```mermaid
flowchart LR
    Call[Method called] --> Key[md5 of\npostIDs + action + params]
    Key --> Seen{Key already\nin static array?}
    Seen -- Yes --> Skip[Return early\nno duplicate processing]
    Seen -- No --> Mark[Mark as processed]
    Mark --> Process[Continue with\nlabel creation]
```

<!-- AUTO-GENERATED:END -->

<!-- MANUAL:START - Safe to edit, preserved on updates -->
<!-- Add custom notes below -->
<!-- MANUAL:END -->
