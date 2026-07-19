# DeQode — Backlog

Deferred ideas. **Do not implement** unless explicitly pulled into the build plan.

---

## Media & storage

* [ ] Full WordPress-style media browser (grid, folders, focal point, attach UI polish).
* [ ] Image pipeline: upload → Lambda (or equivalent) resize/standardize (e.g. max dimension well under 4K) → store derivative as current → keep original as version → delete originals after ~6 months.
* [ ] Spatie Media Library evaluation — only if custom `files` + Filament uploads prove painful.
* [ ] CDN in front of S3 public assets.
* [ ] Per-tenant storage usage dashboard + hard delete of S3 prefix `{tenant_id}/` on account closure (hook exists conceptually; automate + confirm).

## Public front-end

* [ ] Additional templates beyond `default` (theme CSS packs).
* [ ] Wrapper slots: cookie notice, branding footer, powered-by toggle (package-gated).
* [ ] A/B testing, funnels, heatmaps.
* [ ] Multilingual content.

## Modules / product

* [ ] Rich block builder for Content (beyond WYSIWYG).
* [ ] Download block inside Content (vs dedicated File download Qode).
* [ ] Product page / digital manual / warranty as dedicated types.
* [ ] Digital Product Passport / GS1 Digital Link.
* [ ] Product authentication / fraud detection.
* [ ] NFC as first-class channel.
* [ ] Digital business cards product line.

## Billing & growth

* [ ] Stripe (or Paddle) real gateway behind existing `PaymentGatewayInterface`.
* [ ] Proration, coupons, tax engines.
* [ ] Usage-based overage billing.

## Domains

* [ ] Cloudflare for SaaS / automated SSL for custom hostnames at scale.
* [ ] Extra platform short domains in selector (Bitly-style purchase).
* [ ] Apex domain support (ALIAS/ANAME) docs + support path.

## Public codes (Sqids)

* [ ] **Decide before any customer prints codes:** keep lowercase+digits alphabet (`yn1g3rvoejitkqum0fdbc5x78lz6hs92p4aw`, minLength 3) vs letters-only, and/or drop ambiguous chars (`0`/`o`, `1`/`l`). Changing alphabet after print breaks regeneration and support expectations.

## Platform

* [ ] Team RBAC beyond basic members; SSO.
* [ ] API + webhooks; Zapier/Make.
* [ ] Magic links / social login / 2FA.
* [ ] Laravel Nightwatch (or similar) on production; wire into `update.sh` if adopted.
* [ ] White-label beyond custom domain.

## Analytics

* [ ] Advanced dashboards, export, anomaly detection.
* [ ] Deeper unique-visitor strategy.

## Ops

* [ ] RunCloud production setup doc (queues, cron, multi-host TLS for `app.` + `qr.`).
* [ ] Staging environment parity.
