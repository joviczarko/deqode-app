# DeQode — Phased Build Execution Plan

**Source of truth:** [`.cursor/project.md`](project.md) v2.0  
**Status:** Ready for confirmation before coding  
**Rule:** Do not start Layer N+1 until Layer N meets its exit criteria.

This plan turns the locked Vision & Spec into an implementation sequence. No application code should start until this plan is explicitly confirmed.

---

## Preconditions

* Fresh Laravel app already installed in this repo (Filament not yet installed).
* Spec decisions locked: SMB positioning, hybrid Qode model, all V1 tools in scope, layered delivery.
* Scrapbook is **not** a build checklist.

---

## Layer 0 — Platform foundation

**Goal:** Multi-tenant SaaS shell with signup, billing, and two Filament panels — before any Qode exists.

### Work

1. Install Filament; create **Super Admin** and **Tenant** panels.
2. Tenancy model: `tenants`, user↔tenant membership, tenant scoping helpers/middleware.
3. `signup_intents` + verification flow → create tenant + user; generated password mail (optional own password).
4. Packages, subscriptions, invoices, payments, `tenant_feature_overrides`.
5. `PaymentGatewayInterface` + **DemoGateway** (Success / Fail / Cancel); checkout + upgrade UI.
6. Trial modes configurable via package/settings.
7. Superadmin resources: tenants, packages, subscriptions, invoices, payments, signup intents, impersonation, overrides.

### Exit criteria

* [ ] Visitor can complete Signup Intent → verified tenant user in tenant panel.
* [ ] Demo checkout creates subscription + invoice; Fail path is handled.
* [ ] Superadmin can override quota and price for one tenant.
* [ ] Pest tests cover signup intent, demo payment success/fail, tenant isolation smoke.

### Suggested first packages (seed)

Define at least: Free/Trial, Starter, Professional — with Qode count, scan quota, storage, module flags, custom domain flag.

---

## Layer 1 — Qode core

**Goal:** Shared Qode identity, registry, public resolve on primary domain, files, QR download — modules can plug in.

### Work

1. Migrations: `projects`, `qodes`, `files`.
2. `QodeType` enum + **module registry** (config/provider): register type, schema, Filament form fragment, renderer binding, feature gate.
3. Tenant UI: Projects CRUD; Qodes list/create (type picker); edit shell (name, project, status); type switch with wipe confirmation.
4. `public_id` generation (UUID or Hashid); never expose numeric ids publicly.
5. Public route: `GET /q/{publicId}` → resolve Qode → dispatch to module renderer (stub OK for types not yet built).
6. QR image generation + download (SVG/PNG) pointing at public URL.
7. File library upload to S3-compatible disk; attach by id from modules later.

### Exit criteria

* [ ] Create Project + Qode (even with stub type) in tenant panel.
* [ ] `/q/{public_id}` resolves tenant-scoped Qode; inactive Qodes do not serve content.
* [ ] QR download encodes correct URL.
* [ ] Type switch clears `settings` (and related type data) after confirm.
* [ ] Tests: resolve happy path, soft-deleted/paused, cross-tenant public_id must 404.

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

* [ ] Each module creatable, editable, publicly reachable.
* [ ] Disabled module (package flag) hidden from create; gated consistently.
* [ ] Form submit creates lead visible in tenant panel; export works.
* [ ] Feature tests per module (settings validation + public behavior).

---

## Layer 3 — Analytics & custom domains

**Goal:** First-party scan analytics, external tag IDs, branded hostnames.

### Work

1. `visits` table + recorder (prefer queued job from public resolve).
2. Tenant Analytics pages: totals, series, geo/device breakdowns, top Qodes; respect scan quotas for soft warnings/hard limits per package rules.
3. Tenant settings: GA4 / Meta Pixel IDs; optional per-Qode override; inject on HTML renderers.
4. Custom domains: store hostname, verification (DNS TXT or similar), map host → tenant in resolve middleware; serve `/q/{public_id}` on custom host.
5. Document RunCloud/TLS expectations for custom hosts.

### Exit criteria

* [ ] Scan creates visit; dashboard numbers move.
* [ ] External IDs appear in public HTML when configured.
* [ ] Verified custom domain serves the same Qode as primary URL.
* [ ] Tests: visit recorded; unknown host rejected; domain→tenant mapping.

---

## Cross-cutting (every layer)

* Tenant scoping on all Eloquent queries / Filament resources.
* Policies for Qode, File, Lead, Project.
* Pint on dirty PHP; Pest feature tests for each exit criterion.
* Prefer Actions for: CompleteSignup, RecordVisit, CaptureLead, SwitchQodeType, VerifyCustomDomain.
* No OCR, DPP, Zapier, SSO, or Post-MVP items from the spec.

---

## Suggested milestone demos

| Milestone | Demo |
| --- | --- |
| End of Layer 0 | Sign up → demo pay → see tenant in superadmin with override |
| End of Layer 1 | Create Qode → open `/q/...` stub → download QR |
| End of Layer 2 | Packaging story: landing + form lead + PDF download + redirect promo |
| End of Layer 3 | Scan report + `qr.customer.test` custom domain |

---

## After confirmation

When this build plan is approved, execution starts at **Layer 0** only (Filament + tenancy + Signup Intent + billing shell). Module work does not begin until Layer 1 exit criteria pass.
