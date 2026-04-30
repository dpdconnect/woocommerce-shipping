<!--
DOCS_METADATA:
  generated_at: 2026-02-19T10:35:27Z
  git_hash: 8a785aa
  tool_version: 1.0.0
  source_command: /create-documentation
-->

# Documentation Changelog

All notable documentation changes are recorded here.

## [2026-02-19] Regenerated from scratch

### Added

- Initial documentation regenerated from current codebase at git `8a785aa`
- Framework: WordPress/WooCommerce Plugin (DPD Connect for WooCommerce v2.0.2)

### User Documentation Files Created

- `docs/user/01-installation.md` — Requirements, activation, HPOS compatibility
- `docs/user/02-credentials.md` — API credentials, Check Credentials feature
- `docs/user/03-general-settings.md` — Account type, depot, label format, tracking email, download format, package type
- `docs/user/04-company-settings.md` — All company/sender fields, VAT, EORI, HMRC, customs terms
- `docs/user/05-product-defaults.md` — Default HS code, origin country, weight
- `docs/user/06-parcelshop-settings.md` — Google Maps, DPD key, additional parcelshop methods
- `docs/user/07-advanced-settings.md` — Connect URL, callback URL, async threshold, auto-generate labels
- `docs/user/08-shipping-methods.md` — Zone configuration, cost expressions, Saturday settings
- `docs/user/09-creating-labels.md` — Single, bulk, auto-generate, return labels, error messages
- `docs/user/10-parcelshop-checkout.md` — Customer flow, validation, technical notes, checkout type support
- `docs/user/11-batches-and-jobs.md` — Batch/job statuses, async flow, callback mechanism
- `docs/user/12-product-attributes.md` — HS code, customs value, origin country, age check, Fresh/Freeze

### Developer Documentation Files Created

- `docs/developer/01-architecture.md` — High-level architecture, sync/async flows, parcelshop flow
- `docs/developer/02-file-structure.md` — Full annotated directory tree
- `docs/developer/03-label-creation-flow.md` — Step-by-step sync/async flows, product resolution, weight conversion, duplicate prevention
- `docs/developer/04-database-schema.md` — All three custom tables + WordPress options + order/product meta
- `docs/developer/05-wordpress-hooks.md` — All registered actions and filters
- `docs/developer/06-settings-storage.md` — All option keys, field maps, shipping method instance settings
- `docs/developer/07-debugging.md` — Common errors, credential testing, async debugging, JWT cache, DB queries
- `docs/developer/08-key-classes.md` — Option, OrderTransformer, Connection, Shipment, Product, TypeHelper, LabelRequest, Pickup, SettingsDataValidator
- `docs/developer/09-tracking-email.md` — Email content, variables, sending mechanism, localization, customization

---

_Documentation generated with X-Interactive Claude Code Skills Package_
