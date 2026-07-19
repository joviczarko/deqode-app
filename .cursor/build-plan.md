# DeQode — Phased Build Execution Plan

**Source of truth:** [project.md](project.md) v2.3  
**Backlog:** [backlog.md](backlog.md)  
**Status:** Chunk 1a complete — ready for Chunk 1b  
**Rule:** Do not start the next chunk until the current chunk’s exit criteria pass.

Layers are split into **controllable chunks**. Prefer KISS; do not pull backlog items forward.

---

## Preconditions

* Laravel app present; Filament not yet installed.
* Local: single host **`deqode.test`** (Herd).
* Prod hosts: `app.deqode.me` + `qr.deqode.me` (marketing separate).
* Scrapbook is not a checklist.

---

## Chunk 0a — Panels, tenancy, signup

**Goal:** Two Filament panels + hand-rolled tenancy + Signup Intent + default Collection + Free/Trial attach. No billing UI yet beyond “on trial.”

### Work

1. Add root `update.sh` (from Dropstix; PIN `1205`; PHP `/RunCloud/Packages/php84rc/bin/php`; strip Nightwatch unless added later).
2. Install Filament; create **Super Admin** and **Tenant** panels (completely separate).
3. `tenants` with auto-increment starting at **4000**; users ↔ tenants; tenant scoping.
4. On tenant create: default Collection “General.”
5. Signup Intent → verify → tenant + user + generated password mail (optional own password) + **auto Free/Trial** subscription row (even if packages UI is thin).
6. Seed superadmin: `admin@seed.test` / `password`.
7. Config for local vs prod URL hosts / scan path prefix (`/r/{slug}` locally).

### Exit criteria

* [x] Login as seeded superadmin on `deqode.test`.
* [x] Complete signup intent → tenant id ≥ 4000, default Collection, Free/Trial attached.
* [x] Tenant panel loads for that user; cannot see other tenants’ data (smoke test).
* [x] Pest: signup intent happy path; tenant isolation smoke.

---

## Chunk 0b — Packages, Demo billing, overrides

**Goal:** Configurable packages, Demo gateway checkout, invoices, superadmin tenant billing tabs + overrides.

### Work

1. Packages + quotas/feature flags (incl. domain flags for later).
2. Subscriptions, invoices, payments, payment logs.
3. `PaymentGatewayInterface` + DemoGateway (Success / Fail / Cancel).
4. Tenant billing pages: plan, demo checkout, invoices.
5. Superadmin: tenant detail tabs — subscription, invoices, package/quota/price overrides; impersonation.
6. No Stripe.

### Exit criteria

* [x] Demo Success creates invoice + active paid subscription; Fail is handled.
* [x] Superadmin can override quota and price for one tenant.
* [x] Pest: demo success/fail; override applies to effective limits.

---

## Chunk 1a — Domains, Qodes shell, resolve, QR

**Goal:** Publish a Qode stub and open it on local `/r/{slug}`; download QR.

### Work

1. Migrations: `domains`, `qodes` (minimal), seed platform domain for local.
2. Module registry skeleton; Qode types include Redirect + Content (Content may stub).
3. Tenant: create/edit Qode (name, collection, type, status); type switch wipe warning.
4. Default `slug` via **Sqids** (`sqids/sqids`): encode `id` after insert; `minLength` ~8; alphabet from `config/deqode.php` / env — never rotate after print.
5. Resolve by `(domain_id, slug)` only (supports future vanity); local `deqode.test/r/{slug}`; prod host→domain→slug.
6. `simplesoftwareio/simple-qrcode` download (styled enough for V1).
7. URL builder config-driven.

### Exit criteria

* [x] Create Qode → `slug` is Sqids-derived; open `/r/{slug}` (stub OK).
* [x] Re-encoding same `id` with same config yields the same default slug.
* [x] Inactive/unknown slug → 404.
* [x] QR encodes correct local URL.
* [x] Pest: resolve + 404 cases; Sqids round-trip for default slug.

---

## Chunk 1b — Collections, Categories, simple media (S3)

**Goal:** Organize Qodes; upload files to S3 under tenant-prefixed paths.

### Work

1. Collections CRUD (protect default); Categories + pivot; filters on Qode list.
2. `files` table + Filament upload to **S3**; path `{tenant_id}/{uuid-v7}-...`.
3. Simple Files list in tenant panel (not WP media browser).
4. Document that empty AWS env fails loudly until configured.

### Exit criteria

* [ ] Assign Collection/Categories on Qode; filter works.
* [ ] Upload appears in Files; object key is tenant-prefixed.
* [ ] Pest: category attach; file record created (fake S3 disk in tests).

---

## Chunk 1c — Public render stack (wrapper → template → content)

**Goal:** HTML modules render through wrapper + default Pico template.

### Work

1. `layouts/wrapper.blade.php`, `templates/default.blade.php` (Pico CSS), slot for module view.
2. Wire Content module (or stub) through stack; Redirect never uses it.
3. Placeholder hooks for external analytics tags.

### Exit criteria

* [ ] Content-type Qode returns HTML with Pico; wrapper present.
* [ ] Redirect path still bare (when Redirect ships) — no layout.

---

## Chunk 2a — Redirect (built-in)

**Goal:** Strategic dynamic QR redirect.

### Work

1. Settings: URL + status code (302 default).
2. Bare redirect response; enqueue visit stub if visits not ready.
3. Default/recommended type in create flow.

### Exit criteria

* [ ] Scan → 302 to configured URL.
* [ ] Pest: redirect target + status.

---

## Chunk 2b — Content (simple WYSIWYG)

**Goal:** Minimal landing body via Filament rich editor.

### Work

1. Settings: title + HTML/body from Filament rich editor.
2. Public module view inside default template.
3. Optional: insert image from media by id later if trivial — otherwise backlog.

### Exit criteria

* [ ] Edit body in Filament; public page shows it.
* [ ] Pest: content visible on resolve.

---

## Chunk 2c — Link hub

### Work

Repeater links; public list in default template.

### Exit criteria

* [ ] Links render and navigate.
* [ ] Pest: settings validation + public list.

---

## Chunk 2d — Form + Leads

### Work

1. Form field schema in settings; public form via POST/fetch (no Livewire).
2. `leads` + payload JSON; tenant Leads resource (view, export CSV).

### Exit criteria

* [ ] Submit creates lead; visible/exportable in panel.
* [ ] Pest: validation + store.

---

## Chunk 2e — File download Qode

### Work

Settings reference `file_id`; public download page or direct download.

### Exit criteria

* [ ] Authenticated-to-file via Qode (public link) serves from S3 appropriately.
* [ ] Pest: download authorized for active Qode.

---

## Chunk 3a — Visits & analytics UI

### Work

1. `visits` recorder (queued) on resolve.
2. Tenant analytics: totals, series, device/geo basics, top Qodes.
3. Soft/hard scan quota hooks per package.

### Exit criteria

* [ ] Scan increments stats.
* [ ] Pest: visit row created.

---

## Chunk 3b — External pixels + custom domains + vanity

### Work

1. Tenant GA4/Meta IDs; inject on HTML only.
2. Custom domain model + **DNS TXT verification** before verified; resolve on Host.
3. Package-gated vanity slugs + domain selector.
4. Superadmin platform domains list.

### Exit criteria

* [ ] Unverified custom host does not serve.
* [ ] Verified + TXT path works (feature test can fake DNS).
* [ ] Vanity rejected when flag off.
* [ ] Pest: hijack case — second tenant cannot claim same hostname.

---

## Cross-cutting

* Tenant scoping + policies on every resource.
* Actions: CompleteSignup, RecordVisit, CaptureLead, SwitchQodeType, VerifyCustomDomain, …
* Pint dirty; Pest per chunk.
* No backlog items unless explicitly pulled.

---

## Milestone demos

| After | Demo |
| --- | --- |
| 0a | Signup + panels + tenant ≥ 4000 |
| 0b | Demo pay + override |
| 1a | QR → `/r/{slug}` |
| 1b–1c | Media upload + Pico content shell |
| 2a–2b | Redirect + Content |
| 2c–2e | Link hub, form lead, file download |
| 3a–3b | Analytics + custom domain TXT |

---

## Execution note for agents

Start at **Chunk 0a** only. Attach [project.md](project.md), this file, [postulates.md](postulates.md), and [backlog.md](backlog.md). Do not implement backlog items.
