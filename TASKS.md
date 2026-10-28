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
- [ ] Hostinger: upload the 2026-10-28 release and import upgrade-2026-10-28.sql (Page text, package photos, header without phone)
- [x] Ported the Vercel changes of 2026-10-19/20 (see ../jflsolar/PORT-TO-HOSTINGER.md; Codex H3 PASS)
- [ ] Update the admin guide (sign-in now email + password; Inquiries page; Page text menu)
