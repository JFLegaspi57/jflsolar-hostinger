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
