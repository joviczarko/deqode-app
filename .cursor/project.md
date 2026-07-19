# DeQode.me

## Product Vision & Technical Specification

**Version:** 2.3  
**Status:** Locked for V1 execution  
**Related:** [build-plan.md](build-plan.md) · [backlog.md](backlog.md) · [postulates.md](postulates.md)

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

## What DeQode is not (V1)

* Not an Uniqode-style enterprise suite.
* Not “you can do anything” — a small set of compartmentalized tools, done clearly.
* Not a full page builder, WordPress clone, or full WP-style media browser (simple media library only).
* Not a Laravel app nested under a marketing WordPress `/q/` subdirectory.

## Tone

Clear, inviting, package-buyable.

## Guiding product question

> Does this help an SMB run and measure QR marketing in one practical place — especially behind printed packaging and labels?

---

# 2. Principles

See also [postulates.md](postulates.md).

## KISS

Prefer simple, deletable solutions over clever abstractions. Avoid leftovers that make refactors hell. Build only what the current chunk’s exit criteria require.

## Filament first (admin)

Use Filament for tenant and superadmin UI. Public scan pages are **not** Filament / Livewire — Blade (+ minimal JS/fetch for forms).

## Clean business logic

Thin HTTP layer. Actions/Services for workflows. Models represent data.

## Multi-tenant first (hand-rolled)

`tenant_id` on tenant-owned tables + global scopes / policies. No Stancl/Spatie tenancy package unless pain forces it later.

**Tenant primary keys** start at **4000** (migration auto-increment seed) so early customers never see “tenant #1.”

## Package-driven, override-friendly

Packages define defaults. Superadmin can override limits and pricing per tenant.

## Payment abstraction

`PaymentGatewayInterface` + **DemoGateway** only in V1 (no Stripe/Cashier yet).

## OCR

Out of scope for DeQode.

---

# 3. Technology Stack

| Area | Choice |
| --- | --- |
| Backend | Laravel (PHP latest stable) |
| Admin | Two **separate** Filament panels on app host |
| Public CSS | Pico CSS (or similarly small, modern, mobile-first) — not Bootstrap |
| Database | MySQL (prod); local may use MySQL via Herd |
| Hosting | RunCloud |
| Storage | **AWS S3** from day one (`FILESYSTEM_DISK=s3`) |
| QR codes | `simplesoftwareio/simple-qrcode` (styling matters for clients) |
| Public Qode codes | **Sqids** (`sqids/sqids`) — default `slug` from numeric `id` |
| Queue | Laravel Queues |
| Mail | Laravel Mail |
| Cache | Redis (recommended) |
| Deploy | Git + root [`update.sh`](../update.sh) (Dropstix-style; PIN `1205`; PHP `php84rc`) |

---

# 4. Qode Module Architecture (core R&D)

## Mental model

* **Qode** = billable unit = analytics unit.
* One active **module type** at a time (Content, Link hub, …). **Redirect is not a type** — it is an optional per-Qode setting (`Don't redirect` / External URL / Another Qode). Module settings stay editable while a redirect is on.
* Redirect is always **302**. Destination may be an external URL or another Qode that is **not** itself redirecting (no cascades/loops).
* Quota = **Qode count**.

## Built-in vs registry modules

| Module | Role |
| --- | --- |
| **Redirect** | **Not a module.** Per-Qode setting that short-circuits resolve to a bare **302**. |
| **Content** | Registry module. Simple page: Filament rich editor / WYSIWYG body. Smoke-test for wrapper→template→content. Keep minimal. |
| Link hub, Form, File download | Registry modules; ship in later chunks. |

Do not overbuild block builders in V1 Content — rich text (+ optional later image from media) is enough for the first content smoke test.

## Hybrid data model

```
Tenant
  ├── Collections (default "General" on signup)
  │     └── Qodes
  │           ├── settings (JSON)
  │           ├── visits
  │           ├── leads
  │           └── media refs (file ids)
  └── Categories ↔ Qodes
```

## Module contract

Type key, settings schema, Filament UI fragment, public renderer (or redirect response), package gate.

---

# 5. Public render stack

HTML-capable modules only (not Redirect):

```
layouts/wrapper.blade.php     ← analytics tags, future chrome before/after
  └── templates/{name}.blade.php   ← V1: only "default" (Pico CSS + CSS variables)
        └── modules/{type}.blade.php   ← module body
```

* **No Livewire** on the scan host.
* Forms: classic POST or `fetch` to a public endpoint — keep simple.
* Redirect: **bare 302**, skip wrapper/template entirely.

---

# 6. Data Model (essentials)

### `tenants`

IDs start at **4000**. Name, slug, status, analytics settings JSON, timestamps.

### `collections` / `categories` / `category_qode`

Collection required; default Collection on signup. Categories optional M2M for filtering.

### `domains`

`hostname`, `type` (`platform`|`custom`), `tenant_id` nullable, `status`, `is_default`.  
Seed default platform domain from config (prod: `qr.deqode.me`).

### `qodes`

`tenant_id`, `collection_id`, `domain_id`, `slug`, `type`, `status`, `settings` JSON.  
Unique `(domain_id, slug)`.

No separate `public_id` column required: the **default `slug` is the public code**.

### Default public codes (Sqids)

* Package: [`sqids/sqids`](https://sqids.org/php) (successor to Hashids).
* On create: insert Qode → encode numeric `id` → set `slug` (unless a package-gated vanity slug is supplied).
* Resolve **always** by stored `(domain_id, slug)` — do not rely on decode-as-lookup (vanity slugs are not Sqids).
* Regeneration: re-encode `id` with the **same** alphabet + `minLength` → same default code if the row was corrupted.
* Config (lock forever after any customer prints codes):

```
config/deqode.php → sqids.alphabet (custom shuffle via env)
config/deqode.php → sqids.min_length (e.g. 8)
```

* Sqids is **not** encryption; still enforce tenant/authorization. Never expose raw sequential ids in public URLs.
* Vanity: custom `slug` string when package allows; not regenerable from `id`.

### `files` (media library — simple)

Not a full WP media browser. Upload + list + attach by id.

* Store on **S3**.
* Path prefix: `{tenant_id}/{uuid-v7}-...` (or hashed tenant segment) so **all tenant objects can be deleted** when a subscription is closed.
* Soft metadata in DB: disk, path, mime, size, original name, `tenant_id`.

Image pipeline (Lambda resize, 4K cap, versioning, delete originals after N months) → [backlog.md](backlog.md).

### `visits` / `leads`

As in v2.1 — platform analytics source of truth; leads with `payload` JSON.

### SaaS tables

`signup_intents`, `packages`, `subscriptions`, `invoices`, `payments`, `payment_logs`, `tenant_feature_overrides`.

---

# 7. Analytics

* **Layer A:** `visits` table (quotas, in-app reports).
* **Layer B:** optional tenant GA4 / Meta Pixel IDs injected on HTML pages only.

---

# 8. Hosts & local development

## Production

| Host | Role |
| --- | --- |
| `deqode.me` | Marketing (separate) |
| `app.deqode.me` | Both Filament panels + signup/login |
| `qr.deqode.me` | Scan resolve only: `/{slug}` |

Cookies: **host-only** on `app.` (never `Domain=.deqode.me`).

## Local (Herd) — single host

**`http://deqode.test` only** (no `app.` / `qr.` aliases required for V1 local).

* App / Filament / signup: normal panel paths on `deqode.test`.
* Public Qode resolve: path prefix on same host, e.g. **`/r/{slug}`** (or config `SCAN_PATH_PREFIX=r`), so scans never collide with Filament routes.
* Platform domain seed for local: `deqode.test` with path-aware URL builder (`http://deqode.test/r/{slug}`).
* Production URL builder: `https://{hostname}/{slug}` (no `/r/` on dedicated scan host).

Config-driven URL generation so one codebase serves both.

## Custom domains

* CNAME customer host → scan edge (`qr.deqode.me`).
* **DNS TXT (or equivalent) required** before `verified` / before serving (anti-hijack).
* Superadmin may manually attach a domain for early demos without full self-serve UI.
* Unique hostname globally; resolve: Host → domain → slug → Qode.

Package flags: `custom_domains`, `custom_slugs`, `platform_domain_choice`.

---

# 9. Auth & Signup

* Signup Intent → verify → tenant (id ≥ 4000) + default Collection + user + **auto-attach Free/Trial** package (no card).
* Generated password by default; optional own password.
* Seeded superadmin: **`admin@seed.test` / `password`** (local/dev; change in production).

---

# 10. Billing (V1)

* Own tables + Demo gateway (Success / Fail / Cancel).
* No Stripe in V1.
* Packages, trial, invoices, upgrade UI, per-tenant limit + price overrides.
* Superadmin tenant view: tabs for subscription, invoices, package/quota overrides — **not** a copy of the tenant panel.

---

# 11. Administration

Two **completely separate** Filament panels (different purpose, not shared IA):

### Super Admin

Tenants, packages, subscriptions, invoices, payments, signup intents, overrides, platform domains, impersonation. Entering a tenant focuses on **account/billing/quotas**, not editing that tenant’s Qode content (use impersonation to see tenant UI).

### Tenant panel

Dashboard, Qodes, Collections, Categories, Leads, Files (simple), Analytics, Billing, Settings.

---

# 12. V1 scope

Execution order and exit criteria live in [build-plan.md](build-plan.md) (finer chunks). Summary:

* Platform: panels, tenancy, signup, Free/Trial, Demo billing.
* Qode core: collections/categories, domains, resolve, **Sqids default slugs**, QR (simple-qrcode), simple media on S3, render wrapper/template.
* Modules: Content (WYSIWYG), then Link hub, Form+Leads, File download; plus per-Qode Redirect setting (bare 302).
* Analytics + custom domain TXT + vanity slugs.

Deferred ideas: [backlog.md](backlog.md).

## V1 success criteria

1. Sign up → Free/Trial; use Demo checkout for paid path.  
2. Default Collection; create Qode (Content at minimum) with optional Redirect override.  
3. QR downloads; local scan via `deqode.test/r/{slug}`; prod via `qr.deqode.me/{slug}`.  
4. Change destination/content without reprinting.  
5. Visits + leads visible when those chunks ship.  
6. Custom domain TXT + vanity when domain chunk ships.  
7. Superadmin overrides + impersonation.

---

# 13. Coding Standards

* KISS; convention over cleverness.
* Policies + tenant scoping everywhere.
* Enums; validated settings shapes; Actions for workflows.
* Events for subscription, publish, payment, lead.
* Never expose sequential DB ids on public URLs — default codes via **Sqids**; resolve by stored `slug`.
* Soft deletes where recovery matters.
* Pest feature tests per chunk exit criteria.
* Pint on dirty PHP.

---

# 14. Long-term direction

Become the practical OS for printed QR marketing for SMBs; deepen packaging experiences via the same module system once the command center is loved.
