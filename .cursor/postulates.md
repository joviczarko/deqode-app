# Project postulates

Behaviors learned across our SaaS applications. Follow them on DeQode unless the product explicitly defines the opposite.

Portable foundation adapted from our Laravel + Filament SaaS defaults. DeQode-specific notes are called out inline.

---

## Before feature work

1. Execution order and exit criteria live in [build-plan.md](build-plan.md). Do not start the next chunk until the current chunk passes.
2. New ideas → [backlog.md](backlog.md) only — do not pull backlog unless explicitly asked.
3. Filament edits → `search-docs` (Laravel Boost when available) + copy a sibling Resource/Page in the **same** panel.
4. **Filament v5 only** — never use v3 APIs (`Filament\Tables\Actions\*`, `BadgeColumn`, `Form` in `form()`, etc.).

---

## UI

1. **Filament first.** Prefer Filament for panel UI. Custom Blade / Alpine only when Filament cannot do the job.
2. Allowed Blade exceptions: emails, legal/marketing, public Qode renderers, tiny panel render hooks, Demo checkout page.
3. Two panels, separate purpose: **`app`** (tenant product) and **`admin`** (platform). Prefer a **separate admin auth guard** when practical; until then, hard-gate with `is_super_admin` + `canAccessPanel`.
4. Prefer **on-page** forms and relation managers over stacking modals for core account/billing edits when the data is primary workflow.

---

## i18n (target from day 1; retrofit gradually)

5. User-facing strings should become **translation keys** (`__('billing.checkout.success')` + `lang/en/…`), not hardcoded copy. Keys, not English sentences. Market locale ≠ country of the tenant.

---

## Registration

6. **Signup Intent** first (`signup_intents`: email, IP, attempts, expiry, token) → verify → create user + tenant.
7. **Generated password** by default; opt-in “I want to enter my password.”

---

## Tenant & billing entity

8. **Tenant** is the single billing entity (subscriptions, quotas, invoices). Do not split billing across multiple entity types without explicit design.
9. Membership is `users.tenant_id` for V1 (owner). Team RBAC / invites → backlog until pulled.

---

## Billing (always)

10. **`PaymentGatewayInterface`** — app never calls Stripe/etc. directly. Providers: **`demo`** (Success / Fail / Cancel), later production; optional **`stub`** for CI if needed.
11. Ledger entities: `subscriptions`, `invoices`, `payments`, `payment_logs` (+ `checkout_sessions` for pending demo/checkout).
12. Packages live in the **DB** (Filament-editable) with a **fixed catalog of quota + feature keys** in `config/packages.php`. Never free-type JSON keys in admin UI.
13. Package **status** drives catalog behavior: `trial`, `active`, `legacy`, `upgrade_only`, `hidden`.
14. Per-tenant **`tenant_feature_overrides`** for custom deals (quota / feature / price / optional forced package).
15. **`EffectiveEntitlements`** (and later a thin QuotaService) is the only gate for limits/features — not scattered `if ($plan)`.
16. Expired / unpaid plan → read-only product data, block new mutations (enforce when those product mutations exist).

---

## Packages: quotas vs features

17. Keep **quotas** (numeric limits, e.g. `max_qodes`) and **features** (booleans, e.g. `custom_domains`) separate.
18. Admin + override UIs must offer **known keys only** (inputs/toggles/selects from config). No KeyValue / repeater free-text keys.

---

## Admin

19. Admin panel: tenants, packages, subscriptions, invoices/payments, signup intents, overrides, platform domains, impersonation.
20. Entering a tenant focuses on **account / billing / quotas** — not editing that tenant’s Qode content (use impersonation for tenant UI).

---

## Code discipline

21. Thin HTTP layer; **Actions** for workflows.
22. Pest for behavior changed; `vendor/bin/pint --dirty` after PHP edits.
23. No new Composer deps without approval.
24. KISS — build only what the current chunk’s exit criteria require.

---

## Product-specific

25. **OCR — not applicable to DeQode.** Ignore OCR/adapter postulates for this product.
26. Public scan pages are **not** Filament/Livewire — Blade (+ minimal JS/fetch for forms). Redirect Qodes are bare HTTP redirects.
27. Default public Qode codes use **Sqids**; resolve by stored `(domain_id, slug)` only.
28. **Qode edit layout is fixed.** All Qode types share the same Filament create/edit shell:
    - **Main canvas (left, ~2/3):** `Qode name` (`name`) at the top, then that module’s `editFormComponents()` from `QodeModule`.
    - **Sidebar (right, ~1/3):** always `Publish` (status, module, redirect), `Organize` (collection, categories), optional module `editSidebarComponents()`, then `QR code` on edit.
    - Do not embed module fields ad hoc in `QodeForm`; register them on the module class.
    - Header actions on edit: public URL field (copy + open suffix actions) and Delete — not duplicate Open/Copy buttons elsewhere.

---

## Production pitfalls (Laravel + Filament hosts)

28. PHP-FPM `ignore_user_abort` issues can break Livewire; S3 browser uploads need CORS; queue worker required for queued mail/jobs; `trustProxies` behind Cloudflare; `SESSION_SECURE_COOKIE=true` on HTTPS; host-only cookies on `app.` (never parent-domain cookie for multi-host).
