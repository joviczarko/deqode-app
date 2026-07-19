# DeQode.me

## Product Vision & Technical Specification

**Version:** 2.0  
**Status:** Locked for V1 scaffolding

---

# 1. Vision & Positioning

## What DeQode is

DeQode is an **approachable QR marketing command center for small and medium businesses**.

It is **not** a disposable QR code generator. It is the practical place where a company manages redirects, landing pages, link hubs, lead forms, file downloads, and scan analytics — in one product, with clear packages and without enterprise procurement theater.

Traditional QR tools stop once a code is printed. DeQode starts there: every printed code points at a **Qode** — a living digital endpoint whose experience can change without reprinting.

## Hero narrative: labels & packaging

The strongest story — and the one product marketing should lead with — is **labels and packaging**:

* Print a QR once on a bottle, box, or label.
* Later point it at a product story, ingredients page, manual PDF, warranty/feedback form, promo, or seasonal campaign.
* Measure scans and collect first-party leads without juggling agencies, random short-link tools, and five hostings.

That narrative makes the product concrete. The product itself stays broader: any SMB that runs offline→online campaigns (events, posters, menus, packaging, retail) should feel at home.

## What DeQode is not (V1)

* Not an Uniqode-style enterprise suite (SSO mega-nav, digital business cards as the hero, compliance theater first).
* Not “you can do anything” — a small set of **compartmentalized tools**, done clearly.
* Not a full page builder or WordPress clone.

## Tone

Clear, inviting, package-buyable. A marketing manager should understand the product in one sitting and start a trial without a demo call.

## Guiding product question

> Does this help an SMB run and measure QR marketing in one practical place — especially behind printed packaging and labels?

If yes, it belongs. If it only serves enterprise procurement checklists, it waits.

---

# 2. Principles

These apply unless the product explicitly overrides them. They align with project postulates (see [`.cursor/postulates.md`](postulates.md)).

## Filament first

Use Filament components for tenant and superadmin UI whenever possible.

Custom Blade and Alpine.js only when Filament cannot deliver the experience — especially **public-facing Qode renderers**, which are not Filament screens.

## Clean business logic

Thin controllers/Livewire actions. Workflows live in Action/Service classes. Models represent data, not entire application flows.

## Multi-tenant first

Every feature assumes tenant isolation. Nothing accidentally exposes another tenant’s data.

## Package-driven, override-friendly

Packages define default quotas and feature flags. Individual tenants may override limits and pricing without inventing one-off package SKUs.

## Payment abstraction

One payment interface; gateways are swappable via configuration (including a Demo gateway for Success / Fail / Cancel).

## Prefer clarity over infinite configurability

Packages, quotas, and overrides are enough. Do not build an unbounded “everything is a setting” surface that hurts approachability.

## OCR

OCR adapters from other products are **out of scope** for DeQode. Do not design for them.

---

# 3. Technology Stack

| Area | Choice |
| --- | --- |
| Backend | Laravel (PHP latest stable) |
| Admin | Filament (tenant panel + superadmin panel) |
| Database | MySQL |
| Hosting | RunCloud |
| Storage | S3-compatible |
| Queue | Laravel Queues |
| Mail | Laravel Mail |
| Cache | Redis (recommended) |
| Deployment | Git-based via RunCloud |

---

# 4. Qode Module Architecture (core R&D)

Creating “another QR platform” is trivial. **Getting scaffolding right** — one shared identity, common analytics, pluggable type UI, flexible settings, and typed received data — is the real product innovation.

## Mental model

* **Qode** = the product unit = billable unit = analytics unit = the thing the customer manages.
* A Qode has **one active type** at a time (redirect, landing, link hub, form, file download).
* Type may be **switched** with an explicit wipe warning (settings and type-specific received data for that Qode are replaced/cleared). Quota remains **number of Qodes**, not pages vs forms.

Customers care about “this QR does X,” not about separate menus for Pages, Forms, and Redirects.

## Hybrid data model (chosen)

```
Tenant
  └── Projects
        └── Qodes  (one common row)
              ├── settings (JSON, validated per type)
              ├── visits   (shared analytics)
              ├── leads    (form submissions → payload JSON)
              └── files    (referenced from settings / library)
```

### Why hybrid (not WordPress EAV meta)

| Approach | Verdict |
| --- | --- |
| Pure `qodes_meta` key/value | Flexible but awkward for Filament and analytics queries |
| Pure JSON blob only | Fine for settings; weak for querying visits/leads/files |
| **Hybrid (chosen)** | One `qodes` row + validated `settings` JSON + real tables for visits, leads, files |

No `qodes_meta` table in V1 unless a module later proves JSON insufficient for its configuration.

## Module contract

Each Qode type is a **module** that can be enabled or disabled (config / service provider registration is enough for V1).

Every module must provide:

| Concern | Responsibility |
| --- | --- |
| Type key + label | e.g. `redirect`, `landing`, `link_hub`, `form`, `file_download` |
| Settings schema | Validation + DTO/array shape for `qodes.settings` |
| Filament UI | Form components for editing that type |
| Public renderer | Blade/Livewire (or redirect response) for scans |
| Feature / package gate | Enabled flag and any extra quota keys |
| Optional hooks | e.g. on submit, on type switch wipe |

Shared platform owns: identity, QR image, public URL resolve, visit recording, billing quotas on Qode count, file library.

## Enable / disable

Modules register in a central registry (config array or provider). Disabled modules:

* Do not appear in “Create Qode” type picker.
* Do not resolve for existing Qodes of that type (show maintenance/unavailable), or require migration — prefer **hide from create** + **still render existing** until admin migrates.

---

# 5. Data Model

## Core tables

### `tenants`

Organization account: name, slug, status, billing fields, domain settings, external analytics settings (JSON), timestamps.

### `users` + tenant membership

Users belong to tenants (pivot or `tenant_id` + roles). V1: basic team members; no deep RBAC matrix.

### `projects`

Optional organizational folders: `tenant_id`, name, timestamps. Help large accounts group Qodes. Not a separate product line.

### `qodes`

| Column | Purpose |
| --- | --- |
| `id` | Internal PK |
| `tenant_id` | Isolation |
| `project_id` | Nullable organization |
| `name` | Human label |
| `public_id` | Opaque public identifier (UUID or Hashid) for URLs — never sequential IDs |
| `type` | Module type key |
| `status` | draft / active / paused / archived |
| `settings` | JSON — type configuration |
| timestamps + soft deletes | |

Owns: identity, quota counting, QR asset association, analytics join key.

### `files`

Tenant media library: storage path, mime, size, name, `tenant_id`. Qodes reference files by id inside `settings` (never “own” a file exclusively).

### `visits`

One row per scan/hit:

* `tenant_id`, `qode_id`, optional `project_id`
* timestamp, IP hash (privacy-aware), country/city if available
* device, browser, OS, language
* referrer, UTM fields, user agent summary
* optional `is_unique` / visitor fingerprint strategy (document implementation choice at build time)

Indexed for tenant dashboards and quota (monthly scans).

### `leads` (form submissions)

* `tenant_id`, `qode_id`
* `payload` JSON — field values as submitted (schema differs per form)
* optional denormalized `email`, `name` for list/search when present
* timestamps

Different forms → different fields; do not force one rigid lead schema in V1.

### Supporting SaaS tables (conceptual)

`signup_intents`, `packages`, `subscriptions`, `invoices`, `payments`, `payment_logs`, `tenant_feature_overrides`, `custom_domains` (or columns on tenants).

Exact migrations follow this spec at build time.

## Example `settings` shapes (illustrative)

**Redirect**

```json
{
  "url": "https://example.com/campaign",
  "status_code": 302
}
```

**Landing**

```json
{
  "title": "Summer Sparkling",
  "blocks": [
    { "type": "rich_text", "body": "..." },
    { "type": "image", "file_id": 12 },
    { "type": "button", "label": "Buy", "url": "https://..." }
  ]
}
```

**Link hub**

```json
{
  "title": "Find us",
  "links": [
    { "label": "Shop", "url": "https://..." },
    { "label": "Instagram", "url": "https://..." }
  ]
}
```

**Form**

```json
{
  "title": "Warranty registration",
  "fields": [
    { "name": "email", "type": "email", "required": true },
    { "name": "serial", "type": "text", "required": true }
  ],
  "success_message": "Thanks — we received your registration."
}
```

**File download**

```json
{
  "title": "Product manual",
  "file_id": 44,
  "button_label": "Download PDF"
}
```

Each shape is validated by its module schema before save.

---

# 6. Analytics Model

Two layers. Do not conflate them.

## Layer A — Platform analytics (source of truth)

* Every public hit records a `visits` row with `tenant_id` + `qode_id`.
* No separate “DeQode analytics ID” is required for internal reporting: **tenant + Qode `public_id` / id are the join keys**.
* In-app reports: totals, uniques (best-effort), daily/weekly series, countries, devices, browsers, top Qodes, top projects, referrers, form conversion (visits → leads) where applicable.
* Quotas and billing for “monthly scans” use this table — **never** third-party analytics.

## Layer B — Tenant external analytics IDs (optional)

Per tenant (settings), optionally overridden per Qode:

* GA4 Measurement ID
* Meta Pixel ID
* Future: other tags

When set, public page renderers inject the tags. Redirect-only Qodes may skip page tags (no HTML); document that limitation.

This is the “analytics ID per tenant” that matters for customers who already live in Google Analytics. It is **additive**, not a replacement for Layer A.

---

# 7. Multi-tenancy, Domains & URL Scheme

## Hierarchy

```
Tenant → Projects → Qodes → (module settings + visits/leads/files)
```

## Public URL scheme (V1)

**Default (print-friendly, collision-safe):**

`https://deqode.me/q/{public_id}`

The `/q/` prefix avoids clashes with marketing site routes (`/pricing`, `/blog`, etc.).

**Custom domain (in V1, after primary resolve works):**

* Tenant verifies a hostname (e.g. `qr.brand.com`).
* Hostname resolves to tenant → same Qode resolver: `https://qr.brand.com/q/{public_id}`.
* TLS via platform hosting conventions (document operational steps at build time).

Vanity paths like `deqode.me/{tenant}/{id}` are **not** required for V1 if `/q/{public_id}` + custom domains cover print and brand needs.

## Resolve flow

```
Scan / request
  → resolve host (primary or custom domain → tenant)
  → resolve public_id → Qode
  → authorize active + module enabled
  → record visit (async/queue preferred)
  → module renderer (redirect or HTML)
```

---

# 8. Auth & Signup

## Signup Intent (required)

Registrations begin as a **Signup Intent**, not an immediate user+tenant create.

Flow:

```
Visitor → Signup Intent → Email verification → Tenant creation → User creation
```

Store on intent: email, IP, country, browser, referrer, campaign, timestamps, verification state.

Benefits: spam control, abandoned-signup analytics, marketing follow-up, verified email before tenant exists.

## Passwords

Default: system generates a secure password and emails it.  
Optional: “I want to choose my own password.”

## Auth support (V1)

* Email + password
* Password reset

Future: magic links, social login, 2FA.

---

# 9. Billing, Packages & Overrides

Billing is a **core** feature, not an add-on.

## Packages

Define defaults such as:

* Qodes count
* Projects count
* Storage
* Monthly scans
* Team members
* Custom domains (count / enabled)
* Module flags: analytics, forms/CRM, landing, link hub, file download, API (future), etc.

Each capability is **enabled / disabled** or a **quota**.

## Tenant overrides

From superadmin, independently override:

* limits / feature flags
* pricing for that tenant’s package
* trial / expiration when needed

No need to spawn custom package SKUs for one-off deals.

## Trial & checkout

Support configurable trial modes (free trial, no trial, paid immediately, invite-only) without code forks.

## Payments

`PaymentGatewayInterface` with implementations such as Stripe / Paddle / PayPal later, and **DemoGateway** (Success / Fail / Cancel) for local and staging flows.

Every successful charge produces invoice + payment records and billing history. Tenant UI: upgrade, invoices, payment history. Failed payment retry logic as appropriate for the chosen gateway.

Proration can be Post-MVP if needed; upgrades/downgrades must still be usable in V1.

---

# 10. Administration

Two separate Filament panels.

## Super Admin

* Tenants, users, subscriptions, packages
* Invoices, payments, payment logs
* Signup intents
* Feature / price overrides
* Storage / scan usage
* Impersonation
* System health / high-level analytics

## Tenant panel (information architecture)

Keep the mental model simple:

* **Dashboard**
* **Qodes** (create → pick type → edit; QR download)
* **Projects**
* **Leads** (from form Qodes)
* **Files**
* **Analytics**
* **Billing** (plan, checkout, invoices)
* **Settings** (team, custom domain, external analytics IDs)

No separate top-level “Pages” / “Forms” / “Redirects” menus — those are Qode types.

---

# 11. V1 Scope (layered)

Everything below is **in V1**. Build in layers so the platform stays coherent.

## Layer 0 — Platform foundation

* Multi-tenant Filament panels (Super Admin + Tenant)
* Signup Intent → verify → tenant + user
* Generated passwords (optional own password)
* Packages, quotas, per-tenant overrides (limits + price)
* Payment abstraction + Demo gateway + invoices / trial / checkout
* Superadmin: tenants, subscriptions, impersonation

## Layer 1 — Qode core

* Projects, Qodes, module registry
* Public resolver on `deqode.me/q/{public_id}`
* QR image generation/download
* Type switch with wipe warning
* File library (S3-compatible)

## Layer 2 — Modules

* Redirect / short link
* Landing / content page (**simple block builder**, not a full page builder)
* Link-in-bio
* Forms + lead list (view payload, export) — micro CRM
* File download Qode (and/or download as a landing block)

## Layer 3 — Analytics & domains

* Visit tracking + basic reports (totals, unique best-effort, geo, device, top Qodes)
* Tenant external analytics IDs (GA4 / Meta Pixel)
* Custom domain verification + routing

## V1 success criteria

A customer can:

1. Sign up (Signup Intent) and enter trial or checkout via Demo gateway  
2. Create a Project and a Qode of each enabled type  
3. Download a QR that hits the public URL  
4. Change destination/content without reprinting  
5. See scans in Analytics; see form submissions under Leads  
6. Optionally attach a custom domain and external analytics IDs  
7. Superadmin can override quotas/pricing and impersonate for support  

---

# 12. Post-MVP

Explicitly **out of V1** (do not expand Layer 0–3 into these):

* Digital Product Passport / GS1 Digital Link
* Product authentication / fraud detection
* NFC as a first-class channel
* A/B testing, funnels, heatmaps
* AI-assisted content generation
* Zapier / Make / deep CRM sync
* Full white-label beyond custom domain
* Deep team permissions / SSO
* Digital business cards as a product line
* Marketing automation / email campaigns
* Advanced page builder / multilingual content engine

Revisit only when V1 success criteria are met in production use.

---

# 13. Coding Standards

* Convention over cleverness.
* Thin HTTP layer; Actions/Services for workflows.
* Policies for authorization; tenant scoping on every query path.
* Enums for Qode types, statuses, etc. — no magic strings.
* DTOs / validated array shapes for module settings.
* Events for significant actions (subscription created, Qode published, payment received, lead captured).
* Public IDs: UUID or Hashids — never expose sequential database IDs on public URLs.
* Soft deletes where recovery matters.
* Audit important admin/billing actions.
* Readable, testable code over premature abstraction.
* Pest feature tests for signup, billing demo flow, Qode resolve, visit recording, form submit.

---

# 14. Long-term direction

Once the command center is loved by SMBs, deeper packaging/product experiences (manuals, warranty, richer product pages, eventually DPP-class compliance) become natural extensions of the **same Qode module system** — not a second product.

Until then: ship the layered V1, keep modules compartmentalized, and measure whether customers actually run their QR marketing here instead of across scattered tools.
