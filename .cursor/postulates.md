# Project postulates

Behaviors learned across our SaaS applications. Follow them on every project unless the product explicitly defines the opposite.

## Always apply (including DeQode)

1. **Filament first.** Prefer Filament components over custom Blade. Use custom Blade and [Alpine.js](https://alpinejs.dev) only when Filament cannot accomplish the job. (Public Qode renderers are an expected exception — they are visitor-facing, not admin UI.)

2. **Signup Intent.** Registrations start as a Signup Intent entity (email, IP, attempts, referrer, etc.) before creating users/tenants. Reduces spam, preserves emails for follow-up, and gates tenant creation on verification.

3. **Generated passwords.** Auto-generate and email the password unless the user chooses “I want to enter my password.”

4. **Payment abstraction.** One gateway interface so providers can be swapped via configuration.

5. **Superadmin panel.** Global view of tenants, active subscriptions, invoices, payments, etc.

6. **Packages + per-tenant overrides.** Packages define default quotas/features. From tenant admin, independently override limits and/or pricing without creating one-off package SKUs.

7. **Professional SaaS billing** is core, not an add-on:
   1. Clearly defined packages  
   2. Configurable trial modes  
   3. Invoices for every past billing period  
   4. Easy package upgrade page  
   5. Fast checkout  
   6. Demo payment gateway with Success and Error (and Cancel) for testing  

## Product-specific

8. **OCR (not applicable to DeQode).** When a product includes OCR/extraction, abstract it behind a service-agnostic adapter, normalize to a documented JSON schema, and surface per-field confidence in the UI (e.g. 0.5–0.6 yellow, 0.3–0.5 orange, below 0.3 red). **DeQode does not include OCR in V1 or the current roadmap** — ignore this postulate for DeQode implementation work.
