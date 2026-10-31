# Tasks — Hostinger edition

## Done

- [x] Laravel 13 project; data model mirroring the original (same record ids), `lock_version` concurrency
- [x] Public site: home, services, packages (filters, compare ≤3), package/product/project detail, products search, projects, about, contact + quote form, privacy (updated for stored inquiries), terms, 404/500, sitemap, robots, security headers, organization JSON-LD
- [x] Same design: Tailwind tokens and components ported; fonts self-hosted
- [x] Admin: email/password sign-in, owner recovery file, staff with temporary passwords, packages/products/projects/categories CRUD with publish/unpublish/archive/delete, featured, reorder, product search, previews, photo manager (upload/replace/alt/cover/order/delete), datasheets, site content blocks, contact & settings, **inquiries inbox**
- [x] Media outside web root with visibility rules; GD WebP variants
- [x] Release package for File Manager upload (`scripts/release.mjs`), schema for phpMyAdmin
- [x] Content copy from Supabase (`jfl:copy-from-supabase`, export or import) — tested with the real data
- [x] Tests: 13 PHPUnit; browser e2e 40/40 on SQLite, simulated host layout and MariaDB
- [x] Codex security review H1 — passed after fixes (REVIEW.md)
- [x] Docs: README, docs/HOSTINGER.md, AGENTS.md, DECISIONS.md

## Pending

- [x] First install on Hostinger (2026-10-19): temporary address lightseagreen-wildcat-486250.hostingersite.com, preview mode, content exported from Supabase that morning. Checked: 40 pages, 78 images/datasheets, private files blocked, headers, axe clean, 320 px; owner sign-in, edit, photo upload and a test inquiry confirmed by the developer. Note: Hostinger's CDN serves its own robots.txt on the temporary address (pages still send noindex) — recheck on the real domain.
- [x] Owner: recovery file and first owner done; [ ] reset passwords for the other copied staff
- [ ] Final content export from Supabase on switch-over day (freeze edits first)
- [ ] Mailbox + SMTP settings for inquiry alerts (optional)
- [ ] Point the domain to Hostinger; set `SITE_ENV=production`
- [x] Codex visual/UX and accessibility review H2 — passed after fixes (REVIEW.md)
- [x] Deploy repo JFLegaspi57/jflsolar-hostinger published (2026-10-28) with scripts/publish-hostinger.mjs (source + vendor + public/build; root .htaccess; settings/uploads in ../jflsolar-data)
- [ ] Hostinger (JFL account): move .env + storage to jflsolar-data, empty public_html, connect the GitHub repo (auto-deploy), import upgrade-2026-10-28.sql, check the site (incl. that /vendor, /.git, /composer.json are not reachable)
- [x] Ported the Vercel changes of 2026-10-19/20 (see ../jflsolar/PORT-TO-HOSTINGER.md; Codex H3 PASS)
- [ ] Update the admin guide (sign-in now email + password; Inquiries page; Page text menu)

## Online shop (2026-10-31)

- [x] Cart, checkout, PayMongo hosted checkout + signed webhook, Admin → Orders, paid-order email alert, privacy/terms sections (shown only while the shop is on), docs (HOSTINGER.md §11, H7)
- [x] Tests: ShopTest (14, now 21 after the review) — eligibility, catalog pricing, per-metre, validation, PayMongo failure, webhook signature/replay/session/amount checks, idempotency, private order page, staff status rules
- [x] Pay at pickup (office or warehouse, chosen at checkout); shop can go live without PayMongo; staff record counter payments (ShopTest: 26)
- [x] Pickup at the warehouse only, with a "call Sales before pickup" note (ShopTest: 27)
- [x] Printable order summary for the customer + same summary emailed to staff; card payment at the warehouse; Google Maps / Waze directions (ShopTest: 30)
- [x] Shop launched to the public (2026-10-31): SHOP_ENABLED defaults to true
- [x] Member accounts (sign up / sign in / account page with orders), newsletter list with Admin → Subscribers export, and a moderated forum (Admin → Forum) (MembersTest, ForumTest)
- [ ] Owner: write refund / cancellation and pickup rules before going live
- [ ] Owner: PayMongo account → test keys + webhook in .env → test order → live keys
- [x] Independent security review (Claude subagent, 2026-10-31): no BLOCKER; 5 MAJOR + 8 MINOR fixed (inert when off, payment after cancel, payment/staff race, missed-webhook backup check, Enter key in cart, price-change fingerprint, max total, refresh limit, privacy wording, duplicate notes, table-creation race). ShopTest now 21 tests.
- [ ] Codex review of the shop (security + checkout journey) before switching on live payments
- [ ] Browser e2e: add a cart → checkout journey (PayMongo page is external; stop at the redirect)
