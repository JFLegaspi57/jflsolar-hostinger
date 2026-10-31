# Decisions — Hostinger edition

The original prototype's decisions (repository `jflsolar`, DECISIONS.md D1–D14)
still apply to design, content accuracy and behaviour unless replaced below.

## H1 — Rebuild in PHP for Hostinger web hosting (2026-10-17)

JFL Solar bought Hostinger web hosting, which cannot run Node.js apps. The site is
rebuilt in **Laravel 13 / PHP 8.3 / MySQL**, reproducing the Next.js prototype's
pages, design (same Tailwind tokens, compiled locally) and admin. The Vercel +
Supabase prototype stays live and editable until the switch.

## H2 — No SSH or Node.js on the server

Releases are zip files built on the developer machine with `vendor/` and compiled
assets included (`scripts/release.mjs`). The database structure is imported once
in phpMyAdmin (`database/schema/jflsolar.sql`); content moves via `data.sql` +
`media.zip`. The app folder sits **next to** `public_html` (index.php patched,
`usePublicPath`), so code, `.env`, logs and media are never web-reachable.

## H3 — Email + password sign-in, no outside services

Owner request: full autonomy after handover, no dependency on Google or email.
- Staff accounts are local (`users`), passwords bcrypt-hashed, sign-in throttled.
- Owners create staff and reset passwords with one-time temporary passwords; the
  person must choose their own at first sign-in.
- First owner / locked-out owner: a recovery file (`storage/app/admin-recovery.txt`,
  12+ character phrase, valid 24 h) created through File Manager unlocks
  `/admin/recover`. Proves control of the hosting without email. The file is
  consumed atomically under a lock before any account changes (Codex review).
- The last active owner can never be removed, deactivated or demoted.

## H4 — Inquiries stored in the admin

Inquiries are saved in the database (Admin → Inquiries, with status and notes);
an email alert through the Hostinger mailbox is optional and its failure never
loses an inquiry. The privacy notice was updated accordingly. Spam protection as
before (honeypot, signed render time with 3 s minimum, per-client limit using a
salted IP fingerprint in an expiring counter — not stored with the inquiry — and
a daily cap). No proxies are trusted, so IPs cannot be spoofed via headers.

## H5 — Media outside the web root

Photos (GD → WebP 640/1280/2000, never upscaled, EXIF orientation applied,
metadata stripped) and datasheets live in `storage/app/media/<folder>/<owner>/<asset>/`.
`/media/...` serves a file only while its owning item is public (published, not a
sample in production, category published, promotion not hidden after ending);
`/admin/media/...` serves drafts to staff. Same rules as the original RLS.

## H6 — Concurrency and caching

Editable rows carry `lock_version`; saves are conditional on it, so simultaneous
edits are refused instead of overwritten. Public content is cached as plain rows
(Laravel refuses to unserialize objects) and flushed on every admin save; date
rules (promotions) are applied per request.

## H7 — Online shop with PayMongo hosted checkout (2026-10-31)

Owner request: customers can buy priced products online. Choices:
- **PayMongo hosted checkout** (QR Ph, GCash, Maya, cards): the customer pays on
  PayMongo's page, so no card or wallet data touches this server.
- **Only fixed-price, published, non-sample products marked “Available to order”** can
  be bought; quote-only items keep the quote flow. “Per meter” prices sell in metres.
- **Pickup only** — prices on the site are pickup prices; delivery stays a quotation.
- Cart in the session; **prices re-read from the catalog** on every view and at checkout.
- An order is saved before payment (Not paid) and marked paid **only by the signed
  webhook** (HMAC of `t.body`, te/li by key mode, 10-minute replay window), matching the
  order's checkout session and the full amount. Idempotent for PayMongo retries.
- Off by default (`SHOP_ENABLED` + both PayMongo values), so deploying changes nothing.
- Order tables self-create like the visitor tables (no phpMyAdmin step) — but only once
  the shop is switched on; a site that never turns it on gets no tables and no Orders menu.
  They are deliberately not in `database/schema/jflsolar.sql` (same as the visitor tables).
- Backup for a missed webhook: the customer's order page asks PayMongo for the checkout
  session (at most every 20 s per order) and records the payment with the same checks.
- A payment that arrives after staff cancelled the order is recorded and flagged (staff
  note + email alert) instead of being ignored. Payments bump `lock_version`, and staff
  status changes are conditional on the status they saw.
- **Pay at pickup (H8, 2026-10-31, owner request):** customers may reserve online and pay
  in person at the office or warehouse they choose. Works without PayMongo; with PayMongo
  configured the customer chooses. Reservations alert the owner at once and are limited
  per visitor per day (no payment = no-show risk). Only staff mark them paid, recording
  the counter method; online orders are still marked paid only by PayMongo.
  The customer picks cash, GCash, bank transfer or check at checkout (owner request);
  GCash numbers and bank details are sent by staff, not published on the site.
  Pickup is at the warehouse only, and customers are asked to call Sales (the site's main
  number) before coming (owner request, 2026-10-31; `SHOP_PICKUP_LOCATIONS` can re-add the office).
  Card payment at pickup is possible only at the warehouse (card terminal there); checkout
  refuses card with any other pickup place. The order summary page says it is not an official receipt.
- The customer pays exactly the total shown on the checkout page (fingerprint check);
  orders above `SHOP_MAX_TOTAL` (default ₱300,000) go to the quotation form.

- **Own domain is canonical and indexed (2026-10-31, owner request):** on jflsolar.ph /
  www.jflsolar.ph, links, canonical tags, sitemap and robots.txt use https://jflsolar.ph and
  search engines are allowed (live mode), even if APP_URL still holds the Hostinger temporary
  address. `SEARCH_INDEXING=false` now only keeps other hosts (the temporary address) out.
  Only those exact hosts are trusted from the request. Shop rate limits are named per
  feature (a shared `throttle:N,1` counter caused 429s at checkout).

- **Member accounts and newsletter (2026-10-31, owner request):** customers sign up on a
  separate `member` auth guard (never the staff `web` guard; nothing calls `shouldUse`).
  Sessions are tied to a password stamp, so a reset or change ends other sessions. No email
  verification yet (site email is not configured): reset links need working mail. Guest
  checkout stays. Newsletter: footer form needs a consent tick and never re-subscribes someone
  who left; staff export a CSV with personal unsubscribe links (no sending from the site).
  A forum was built and then removed the same day at the owner's request (its empty
  tables, if created, are left in the database; nothing reads them).
  Tables are created on first use (`Members`/`Newsletter::ensureTables`).

- **Member profiles and activity (2026-10-31, owner request):** members may add a profile photo
  (square 320px WebP, re-encoded, metadata dropped; served only to the member and staff) and
  Facebook/YouTube/Instagram/TikTok links (checked against each site's own addresses). Staff
  see, per member, sign-ins with approximate place (GeoIP city/country; IP not stored) and pages
  opened while signed in (not when DNT/GPC is sent; never while staff are signed in), kept ~13
  months, plus a members CSV. The privacy notice says so.

