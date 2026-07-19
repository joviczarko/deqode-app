# DeQode — Phased Build Execution Plan

**Source of truth:** [`.cursor/project.md`](project.md) v2.1  
**Status:** Ready for confirmation before coding  
**Rule:** Do not start Layer N+1 until Layer N meets its exit criteria.

This plan turns the locked Vision & Spec into an implementation sequence. No application code should start until this plan is explicitly confirmed.

---

## Preconditions

* Fresh Laravel app already installed in this repo (Filament not yet installed).
* Spec decisions locked: SMB positioning, hybrid Qode model, Collections + Categories, host split (`deqode.me` / `app.deqode.me` / `qr.deqode.me`), layered V1.
* Scrapbook is **not** a build checklist.
* Do **not** deploy the SaaS as a subdirectory under a WordPress marketing site.

---

## Layer 0 — Platform foundation

**Goal:** Multi-tenant SaaS shell with signup, billing, and two Filament panels on `app.deqode.me` — before any Qode exists.

### Work

1. Install Filament; create **Super Admin** and **Tenant** panels (configured for `app.deqode.me` in production; local Herd host for dev).
2. Tenancy model: `tenants`, user↔tenant membership, tenant scoping helpers/middleware.
3. `signup_intents` + verification flow → create tenant + **default Collection** + user; generated password mail (optional own password).
4. Packages, subscriptions, invoices, payments, `tenant_feature_overrides` (include domain-related flags: `custom_domains`, `custom_slugs`, `platform_domain_choice`).
5. `PaymentGatewayInterface` + **DemoGateway** (Success / Fail / Cancel); checkout + upgrade UI.
6. Trial modes configurable via package/settings.
7. Superadmin resources: tenants, packages, subscriptions, invoices, payments, signup intents, impersonation, overrides.

### Exit criteria

* [ ] Visitor can complete Signup Intent → verified tenant user in tenant panel; default Collection exists.
* [ ] Demo checkout creates subscription + invoice; Fail path is handled.
* [ ] Superadmin can override quota and price for one tenant.
* [ ] Pest tests cover signup intent, demo payment success/fail, tenant isolation smoke.

### Suggested first packages (seed)

Define at least: Free/Trial, Starter, Professional — with Qode count, scan quota, storage, module flags, custom domain / custom slug flags.

---

## Layer 1 — Qode core

**Goal:** Shared Qode identity, Collections/Categories, domain+slug resolve on `qr.deqode.me`, files, QR download — modules can plug in.

### Work

1. Migrations: `collections`, `categories`, `category_qode`, `domains`, `qodes`, `files`.
2. Seed platform domain `qr.deqode.me` (`type=platform`, `is_default=true`).
3. `QodeType` enum + **module registry** (config/provider): register type, schema, Filament form fragment, renderer binding, feature gate.
4. Tenant UI: Collections CRUD (protect default); Categories CRUD; Qodes list/create (type picker, collection, categories); edit shell (name, collection, categories, status, domain/slug fields); type switch with wipe confirmation.
5. On Qode create: assign default Collection; set `domain_id` to default platform domain; set `slug` = generated `public_id`.
6. Public resolve (scan host only): Host → `domains` row → `{slug}` → Qode → module renderer (stub OK for types not yet built). Flat path: `https://qr.deqode.me/{slug}` — **no** `/q/` prefix on the scan host.
7. Scaffold stubs: custom domain model fields + resolve branch (verification UI may be Layer 3); do not wire scan host to `app.deqode.me`.
8. QR image generation + download (SVG/PNG) encoding the full public URL for the Qode’s domain+slug.
9. File library upload to S3-compatible disk; attach by id from modules later.
10. Session cookies: host-only on app host (never `Domain=.deqode.me`).

### Exit criteria

* [ ] Create Collection / Category + Qode (even with stub type) in tenant panel; new Qodes land in default Collection.
* [ ] `qr.deqode.me/{slug}` (or local scan host equivalent) resolves Qode; inactive Qodes do not serve content.
* [ ] Unknown host or unknown slug → 404; slug unique per domain.
* [ ] QR download encodes correct URL.
* [ ] Type switch clears `settings` (and related type data) after confirm.
* [ ] Tests: resolve happy path, soft-deleted/paused, wrong domain/slug 404, cross-tenant isolation.

---

## Layer 2 — Modules

**Goal:** Ship the five customer-facing tools as registry modules.

Implement in this order (fastest value → richer UI):

### 2a Redirect

* Settings: URL + status code.
* Renderer: redirect response; still record visit (Layer 3 may land in parallel — at minimum enqueue visit stub).

### 2b File download

* Settings: `file_id`, title, button label.
* Renderer: download page or direct download; uses Files library.

### 2c Link hub

* Settings: title + repeater links.
* Public template: simple branded link list.

### 2d Landing (simple blocks)

* Block types V1: rich text, image (`file_id`), button, optional download button.
* Filament repeater/builder for blocks; public Blade composition.
* Not a full page builder.

### 2e Form + Leads

* Settings: title, fields schema, success message.
* `leads` table with `payload` JSON (+ optional email/name columns).
* Public form submit → validate against schema → store lead.
* Tenant **Leads** resource: list, view payload, CSV export.

### Exit criteria

* [ ] Each module creatable, editable, publicly reachable on scan host.
* [ ] Disabled module (package flag) hidden from create; gated consistently.
* [ ] Form submit creates lead visible in tenant panel; export works.
* [ ] Feature tests per module (settings validation + public behavior).

---

## Layer 3 — Analytics & domains polish

**Goal:** First-party scan analytics, external tag IDs, custom domains, vanity slugs, domain selector.

### Work

1. `visits` table + recorder (prefer queued job from public resolve); store `collection_id` when present.
2. Tenant Analytics pages: totals, series, geo/device breakdowns, top Qodes / Collections; respect scan quotas for soft warnings/hard limits per package rules.
3. Tenant settings: GA4 / Meta Pixel IDs; optional per-Qode override; inject on HTML renderers.
4. Custom domains: verification (DNS TXT or similar), status transitions, resolve via same host→domain→slug path; flat `https://{custom}/{slug}`.
5. Package-gated vanity `slug` editing + choosing among allowed platform/custom domains.
6. Superadmin: manage platform domains (add future short domains without code changes).
7. Document RunCloud/TLS expectations for `app.`, `qr.`, and custom hosts.

### Exit criteria

* [ ] Scan creates visit; dashboard numbers move.
* [ ] External IDs appear in public HTML when configured.
* [ ] Verified custom domain serves the Qode at `https://{custom}/{slug}`.
* [ ] Vanity slug rejected when package disallows; uniqueness enforced per domain.
* [ ] Tests: visit recorded; unknown host rejected; domain→slug mapping; platform domain seed.

---

## Cross-cutting (every layer)

* Tenant scoping on all Eloquent queries / Filament resources.
* Policies for Qode, File, Lead, Collection, Category, Domain (custom).
* Pint on dirty PHP; Pest feature tests for each exit criterion.
* Prefer Actions for: CompleteSignup, RecordVisit, CaptureLead, SwitchQodeType, VerifyCustomDomain, AssignQodeDomain.
* No OCR, DPP, Zapier, SSO, or Post-MVP items from the spec.

---

## Suggested milestone demos

| Milestone | Demo |
| --- | --- |
| End of Layer 0 | Sign up on app host → demo pay → default Collection → superadmin override |
| End of Layer 1 | Create Qode → open `qr…/{slug}` stub → download QR |
| End of Layer 2 | Packaging story: landing + form lead + PDF download + redirect promo |
| End of Layer 3 | Scan report + custom domain + vanity slug |

---

## After confirmation

When this build plan is approved, execution starts at **Layer 0** only (Filament + tenancy + Signup Intent + billing shell + default Collection). Module work does not begin until Layer 1 exit criteria pass.
