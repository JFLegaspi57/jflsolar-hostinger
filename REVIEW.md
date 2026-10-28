# Review log — Hostinger edition

Independent reviews by Codex (read-only). Earlier rounds for the Next.js
prototype are in the `jflsolar` repository's REVIEW.md.

## Round H1 — Security and code review of the PHP port (Codex, 2026-10-18)

Static review of auth, recovery, CSRF, XSS, uploads, media visibility, redirects,
inquiry protection, concurrency, release layout and the data copy command.
**Round 1: FAIL** (4 MAJOR, 3 MINOR). **Round 2: PASS** (6 verified, 1 downgraded to minor and fixed).

- [x] MAJOR — All proxies trusted, so X-Forwarded-For could be spoofed to dodge rate limits → no proxies trusted; client IP is the connection address.
- [x] MAJOR — Recovery file could be used by two simultaneous requests → whole recovery runs under a database-backed lock; file consumed by atomic rename before any account change.
- [x] MAJOR — Recovery claimed deletion even if the file could not be removed → recovery refuses to proceed unless the file is consumed; message is accurate if the renamed copy remains (round 2 minor).
- [x] MAJOR — No database schema for phpMyAdmin installs → `database/schema/jflsolar.sql` generated from the migrations on MariaDB 11.8; the release build fails without it.
- [x] MINOR — Images narrower than 640 px were stored but unservable → width pattern accepts bounded numeric widths; asset file list still enforced.
- [x] MINOR — Media of packages hidden after an ended promotion stayed reachable → same predicate as the public listing.
- [x] MINOR — Data export escaping depended on backslash escapes → strings exported as `_utf8mb4 X'…'` hex literals; import verified under `NO_BACKSLASH_ESCAPES`.

Evidence at round 2: 13 PHPUnit tests; 40-check browser test passing on SQLite, on a
simulated Hostinger layout and on MariaDB 11.8; real content (9 packages, 16 products,
30 media records, 46 files, 3 staff) imported through the phpMyAdmin path and served.
Not yet verified: MySQL 8 itself, and the real Hostinger server.

## Round H2 — Visual UX and accessibility review (Codex, 2026-10-18)

Codex reviewed 97 rendered screenshots (57 public, 40 admin, 1280 and 390 px) against
the source and the prototype's UX-1/UX-2 standard. Before the review, visible screen-reader
labels on confirm buttons were fixed (5de1627).
**Round 1: FAIL** (2 MAJOR, 5 MINOR). **Recheck: PASS** (all 7 closed).

- [x] MAJOR — Product card image areas varied in height → fixed 4:3 frame with the image absolutely positioned and `object-contain` (`fit` prop on `x-media-image`).
- [x] MAJOR — Form control borders `#9aa4b1` ≈ 2.5:1 on white → steel-500 `#6f7a89` ≈ 4.4:1 on every input, select, textarea and checkbox.
- [x] MINOR — Dyness card looked blank → the stored photo is valid (white product on white); the blank was a lazy-load capture artefact. Visible in the recapture.
- [x] MINOR — Contact card icon squashed into an oval → icon `shrink-0`, text column `min-w-0` with wrapping.
- [x] MINOR — Datasheet feedback appeared in the photo manager → all datasheet outcomes redirect to `#datasheet`; success shown there, errors tied to the file input.
- [x] MINOR — Recovery steps named only `storage/app` → now `jflsolar-app/storage/app`, next to `public_html`.
- [x] MINOR — Failed image replacement closed its panel → panel reopens with an inline error linked by `aria-describedby`.

Evidence: 13 PHPUnit tests; browser test 42/42 (two new checks for the error paths) on an
isolated SQLite instance; axe no violations on public pages, login and recover.

## Round H3 — Port of the Vercel changes of 2026-10-19/20 (Codex, 2026-10-28)

Header phone removed, package photos on /packages, Admin → Page text (107 fields,
registry generated from the Vercel edition), page_texts table + upgrade-2026-10-28.sql,
data copy of page_texts and inquiries. Reviewed with screenshots (admin Page text,
/packages at 1280/390, header).
**Round 1: FAIL** (1 MAJOR) → **Round 2: PASS**.

- [x] MAJOR — After a refused or invalid save the form restored the typed values but carried the newest version, so a second Save overwrote another editor’s changes → the redisplayed form keeps the version its restored values were based on; regression test added.

Evidence: `php artisan test` 23/23 (new PageTextTest, registry/cleaning unit test), browser e2e 47/47 (edit → public page → restore), upgrade-2026-10-28.sql imported twice on MariaDB 11.8 without duplicates, schema regenerated from the migrations.
