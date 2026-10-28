# Agent collaboration rules — JFL Solar website (Hostinger edition)

This repository is the **Laravel 13 / PHP 8.3 / MySQL** edition of the JFL Solar
website (solar installation, consultancy and equipment distribution, Philippines),
built to run on **Hostinger web hosting** (PHP + MySQL only; no Node.js on the
server). It replaces the earlier Next.js + Supabase prototype (separate
repository `jflsolar`), whose design, content rules and behaviour it reproduces.
It is a separate business from Provice — never reuse Provice branding, copy,
business details or design.

## Roles

- **Claude Code — primary developer.** Implementation, documentation, remediation.
- **Codex — independent QA/QE and UX reviewer.** Reviews security, accessibility,
  customer journeys and deployment readiness; records findings in `REVIEW.md` and
  rechecks fixes. Codex does not edit application code unless explicitly assigned.

## Workflow

1. Read `TASKS.md` and `DECISIONS.md` before changing architecture.
2. Before calling work complete: `composer test`, the browser test
   (`tests/browser/admin-e2e.mjs`, see README), `npm run build`, and — before any
   release — `node scripts/release.mjs` plus a smoke test of the extracted package.
3. Never claim a review, live test or deployment happened unless it did.

## Content accuracy rules (non-negotiable)

- No invented testimonials, ratings, client counts, certifications, warranty
  periods, business hours, map coordinates or email addresses.
- Sample/demo records carry `is_sample = true` and are hidden on the live site
  (`SITE_ENV=production`).
- Claims needing owner confirmation are content blocks with
  `claim_status = 'needs_confirmation'`; they appear only on preview sites, flagged.
- Listing a brand does not imply authorised-distributor status.

## Development rules

- Server-rendered Blade, minimal JavaScript (progressive enhancement only).
- Every form posts normally and validates on the server; failed saves return the
  typed values. Admin writes use optimistic concurrency (`lock_version`).
- Staff sign-in is email + password only; no dependency on Google or email.
- Media files live outside the web root (`storage/app/media`) and are served by
  controllers that check the owning item's visibility.
- Mobile-first (works at 320 px), WCAG 2.2 AA.
- No secrets in Git; document every variable in `.env.example`.
