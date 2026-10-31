# Running the JFL Solar website on Hostinger

This edition is a plain **PHP 8.3 + MySQL** website (Laravel), built for Hostinger
**web hosting**. Nothing needs Node.js, SSH or Composer on the server: everything
is done in **hPanel**, **File Manager** and **phpMyAdmin**.

The release package keeps the code **outside** the public web folder:

```
domains/<your-domain>/
├── public_html/      ← the only folder visitors can reach (index.php, images, CSS)
└── jflsolar-app/     ← code, settings (.env), photos (storage/app/media), logs
```

---

## 1. Before you start

- A Hostinger plan with PHP 8.3 or newer and MySQL (all web hosting plans).
- From the developer: the release zip (`jflsolar-release-<date>.zip`), and — when
  moving from the old site — `data.sql` and `media.zip` (see section 6).

## 2. First install

1. **Website** — hPanel → **Websites** → **Add website** → choose a **PHP/HTML**
   website (not Node.js) for your domain. If Hostinger put a default page in
   `public_html` (`default.php`, `index.html`), delete it.
2. **PHP version** — hPanel → *Advanced* → **PHP Configuration** → **PHP 8.3**
   (or newer). The extensions this site needs (`pdo_mysql`, `gd`, `exif`,
   `mbstring`, `intl`, `fileinfo`, `zip`, `curl`) are on by default; leave them on.
3. **Database** — hPanel → *Databases* → **Management**: create a database and a
   user. Write down the three values Hostinger shows (they look like
   `u123456789_jfl`): database name, user name, password.
4. **Tables** — open **phpMyAdmin** for that database → **Import** →
   choose `database-schema.sql` from the release zip → **Import**.
5. **Content** — still in phpMyAdmin → **Import** → `data.sql` (from the move,
   section 6). Without it the site has no contact details and shows an error.
6. **Files** — **File Manager** → open `domains/<your-domain>/` (the folder that
   contains `public_html`) → **Upload** the release zip → right-click → **Extract**
   here. You now have `jflsolar-app/` next to `public_html/`.
7. **Photos** — upload `media.zip` into `jflsolar-app/storage/app/` and **Extract**
   there. You get `jflsolar-app/storage/app/media/…`.
8. **Settings** — in `jflsolar-app/`, copy `.env.example` to `.env`, open it
   (right-click → Edit) and fill in:

   | Setting | Value |
   | --- | --- |
   | `APP_KEY` | from the developer (`php artisan key:generate --show`) — keep it secret |
   | `APP_URL` | `https://jflsolar.ph` (your address, no trailing slash) |
   | `SITE_ENV` | `preview` while testing; `production` when you go live |
   | `SEARCH_INDEXING` | `false` while a live-mode site is on a temporary address (keeps a duplicate out of Google); `true` on the real domain |
   | `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | from step 3 (`DB_HOST=localhost`) |
   | `INQUIRY_NOTIFY_EMAIL` | where new-inquiry alerts go, e.g. `info@jflsolar.ph` (optional) |
   | `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | the Hostinger mailbox that sends the alerts (section 5) |

9. **First owner account** — in File Manager go to `jflsolar-app/storage/app/` (GitHub deployments: `jflsolar-data/storage/app/`),
   create a file named `admin-recovery.txt`, type a phrase of at least 12
   characters, and save. Open `https://<your-domain>/admin/recover` within 24
   hours. If there are no staff accounts it creates the first owner; if staff
   were copied from the old site, enter an **owner's** email to set their
   password. The file is deleted automatically when it succeeds.
10. **HTTPS** — hPanel → *Security* → **SSL**: make sure the certificate is active.
11. **Check** — open the home page, a package, a product with a datasheet, the
    Contact page (send a test inquiry), and sign in at `/admin`.

## 3. Staff accounts (no email needed)

- Owners add people under **Admin → Staff**: name, email and role. The site shows
  a **temporary password once** — give it to the person yourself. At first
  sign-in they must choose their own password.
- Forgotten password: an owner clicks **Reset password** on the Staff page and
  passes on the new temporary password.
- Staff copied from the old site have no password yet: after the first owner has
  signed in, reset each person's password from the Staff page.
- A locked-out owner uses the recovery file (step 2.9) again. Only someone with
  access to the Hostinger account can do this.

## 4. Going live with your domain

1. In hPanel, connect the domain to this website (if it is registered elsewhere,
   point its nameservers to the two Hostinger shows, or add the A records it lists).
2. In `.env` set `APP_URL=https://jflsolar.ph`, `SITE_ENV=production` and `SEARCH_INDEXING=true`, save.
3. Live mode hides sample items and unconfirmed claims, and lets search engines
   index the site. Check **Admin → Overview → Before launch** first.

## 5. Inquiry email alerts (optional)

Inquiries are always saved under **Admin → Inquiries**. To also get an email:

1. hPanel → **Emails** → create a mailbox on your domain, e.g. `info@jflsolar.ph`.
2. In `.env`: `INQUIRY_NOTIFY_EMAIL=info@jflsolar.ph`, `MAIL_USERNAME=info@jflsolar.ph`,
   `MAIL_PASSWORD=<that mailbox's password>`, `MAIL_FROM_ADDRESS=info@jflsolar.ph`.
   The mail server settings are pre-filled for Hostinger (`smtp.hostinger.com`, port 465).
3. Send a test inquiry; it should arrive within a minute. **Reply** goes to the customer.

## 6. Moving content from the old (Vercel + Supabase) site

On the developer machine, with the old site's Supabase address and secret key in
this project's `.env` (`SUPABASE_URL`, `SUPABASE_SECRET_KEY` — never on the server):

```bash
php artisan jfl:copy-from-supabase --export=storage/app/export
```

This writes `storage/app/export/data.sql` (all content and the staff list) and
`media.zip` (every photo and datasheet). Import and extract them as in steps 2.5
and 2.7. Ask staff to stop editing the old admin before you export; changes made
afterwards are not copied.

## 7. Updating the website (new release)

1. The developer builds a release (`node scripts/release.mjs`) and sends the zip.
2. File Manager → `domains/<your-domain>/` → upload → **Extract**, allowing it to
   replace existing files. Your `.env` and `storage/` (photos, logs) are kept —
   never delete them.

   If File Manager only extracts into a **new folder** (e.g. `update`):
   - In `update/jflsolar-app/`, select **everything except `storage`** → **Move** to
     `jflsolar-app/`, replacing existing items. (The zip has no `.env`, so yours stays.)
   - In `update/public_html/`, select everything → **Move** to `public_html/`, replacing.
   - Delete the `update` folder and the zip. Never move or delete `jflsolar-app/storage`
     or `jflsolar-app/.env`.
3. Import, in phpMyAdmin, every **upgrade-*.sql** in the zip that is newer than your
   last update (oldest first). Each is safe to import twice.
4. Reload the site. (If pages look old, clear the browser cache.)

### Updating the database structure (developers)

After changing migrations, regenerate `database/schema/jflsolar.sql` from a local
MySQL/MariaDB (see README) and write an `upgrade-<date>.sql` with the matching
`ALTER TABLE` statements for existing sites. The release build refuses to run
without the schema file.

## 8. Backups

- Hostinger keeps automatic backups (hPanel → **Backups**).
- Your own copy, monthly: phpMyAdmin → **Export** (the whole database), and
  File Manager → download `jflsolar-app/storage/app/media` (compress first) and
  `jflsolar-app/.env`.

## 9. Troubleshooting

| Problem | What to do |
| --- | --- |
| “Something went wrong” page | Open `jflsolar-app/storage/logs/` in File Manager; the newest `laravel-*.log` says why. Never set `APP_DEBUG=true` on the live site. |
| Error about `site_settings` | The content (`data.sql`) was not imported. |
| Photos missing | `media.zip` was not extracted into `jflsolar-app/storage/app/`, or the item is a draft. |
| “The form expired” | The page was open a long time; press Send/Save again — the typed text is kept. |
| Locked out of the admin | Use the recovery file (section 2, step 9). |
| Inquiry emails not arriving | Check the spam folder and the `MAIL_*` values; inquiries are still saved in the admin. |

## 10. Deploying from GitHub (Hostinger Git) — instead of zip releases

The GitHub repository `JFLegaspi57/jflsolar-hostinger` holds a **deploy-ready copy**:
the source code plus `vendor/` (PHP components) and `public/build/` (compiled CSS
and JavaScript), because Hostinger's Git deployment runs no build and no Composer —
the committed files are the files served.

```
domains/<your-domain>/
├── public_html/      ← the GitHub repository (replaced on every deploy)
│   ├── .htaccess     ← sends every request to public/ (nothing else is reachable)
│   ├── public/       ← the actual website (index.php, images, CSS)
│   └── app/, vendor/, …
└── jflsolar-data/    ← NOT in Git, never touched by deploys
    ├── .env          ← settings (database password, APP_KEY, …)
    └── storage/      ← photos (app/media), sessions, cache, logs
```

The site uses `jflsolar-data/` automatically whenever `jflsolar-data/.env` exists.
A site first installed from a zip already has `.env` and `storage/` in `jflsolar-app/`
next to `public_html`; when there is no `jflsolar-data/`, that folder is used instead.

### One-time switch from the zip install

1. **Connect the repository** (JFL's Hostinger account): hPanel → **Websites** →
   this site → **Advanced → Git** (or *GitHub deployment*) → **Connect with GitHub**,
   allow access to `jflsolar-hostinger`, branch **main**, directory **public_html**
   (the default). Turn **Auto-deployment** on, then **Deploy**. If Hostinger says the
   folder must be empty, delete everything inside `public_html` (not the folder) and
   deploy again — nothing the site needs is kept there.
2. **Do not delete `jflsolar-app/`**: its `.env` and `storage/` (photos) stay in use.
   (Optionally, later: create `jflsolar-data/` and move `.env` and `storage/` into it;
   then `jflsolar-app/` can be deleted.)
3. **Database:** import any `database/upgrades/upgrade-*.sql` you have not imported yet.
4. **Check** the home page, a product photo, the Contact form and `/admin`.

### Publishing a new version (developer)

```bash
node scripts/publish-hostinger.mjs https://github.com/JFLegaspi57/jflsolar-hostinger.git --push
```

This builds CSS/JS, copies the last commit, installs production PHP components and
pushes one commit to `main`; with auto-deployment on, Hostinger deploys it within a
minute. Database changes still come as `upgrade-*.sql` files to import in phpMyAdmin.

### Fresh install from GitHub (no zip)

Follow section 2, but: skip the zip (steps 6–7); create `jflsolar-data/` with
`.env` (from `.env.example`) and `storage/app/media`, `storage/framework/cache/data`,
`storage/framework/sessions`, `storage/framework/views`, `storage/logs`; put the
recovery file in `jflsolar-data/storage/app/`; then connect the repository as above.

## 11. Online shop (cart + PayMongo payments)

Customers can add **fixed-price, “Available to order”** products to a cart and pay online
through PayMongo (QR Ph, GCash, Maya, cards). Orders are **pickup only**, at the
warehouse address in Contact & settings (or the office if no warehouse is listed).
Products on “Request price”, sample listings and items not “Available to order” keep
the normal quote button. Products whose price note says “per meter” are sold in metres.

The shop is **off** until it is switched on in `.env`, so updating the website changes
nothing by itself. The order tables are created automatically on first use (no
phpMyAdmin step).

**Before going live, write your refund / cancellation and pickup rules** (for example
on the Terms page through Page text or a content block). PayMongo also asks for them
during account activation.

### Switching it on — test mode first

1. Create a PayMongo account at paymongo.com. In the Dashboard → **Developers → API keys**,
   copy the **secret key** (`sk_test_…`).
2. Dashboard → **Developers → Webhooks → Create webhook**: URL
   `https://<your domain>/webhooks/paymongo`, event **checkout_session.payment.paid**.
   Copy its **signing secret** (`whsk_…`).
3. In File Manager open `jflsolar-data/.env` (zip installs: `jflsolar-app/.env`) and add:

   | Name | Value |
   | --- | --- |
   | `SHOP_ENABLED` | `true` |
   | `PAYMONGO_SECRET_KEY` | the `sk_test_…` key |
   | `PAYMONGO_WEBHOOK_SECRET` | the `whsk_…` secret |
   | `PAYMONGO_METHODS` | optional, default `qrph,gcash,paymaya,card` |
   | `ORDER_NOTIFY_EMAIL` | optional; default is `INQUIRY_NOTIFY_EMAIL` |
   | `SHOP_MAX_TOTAL` | optional; largest order paid online, default `300000` (₱) |

4. Place a test order on the site (PayMongo's test page lets you simulate a payment).
   It should appear in **Admin → Orders → To prepare** within seconds, and the
   customer's order page should change to “paid”.
5. When PayMongo activates the account, replace both values with the **live** key
   (`sk_live_…`) and the secret of a **live** webhook, then place one small real order.

To switch the shop off again, set `SHOP_ENABLED=false`. Existing orders stay in the admin.

### How orders work

- Prices are always taken from the product as published at checkout — a customer
  cannot change them. Items that become unavailable drop out of carts.
- An order is saved as **Not paid** when the customer opens PayMongo's page, and moves
  to **To prepare** only when PayMongo's signed webhook confirms the full amount. Staff
  cannot mark an order paid by hand. Never release items for a **Not paid** order.
- Admin → Orders: **Mark ready for pickup**, then **Mark picked up**. **Cancel order**
  does not refund anyone — refunds are made in the PayMongo dashboard. If a customer
  pays on a payment page that was still open after you cancelled, the order shows a red
  “Paid after it was cancelled” notice and you get an email: refund or reinstate it.
- If PayMongo's message to the site is ever missed, the customer's order page checks with
  PayMongo itself, so paid orders still appear.
- The customer gets PayMongo's email receipt and a private order page (link in the
  browser after paying) that shows the current status.
