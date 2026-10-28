// Builds a ready-to-upload package for Hostinger web hosting (no SSH, no
// Node.js, no Composer needed on the server).
//
//   node scripts/release.mjs
//
// Output: release/jflsolar-release-<date>.zip containing
//   jflsolar-app/   Laravel application (with vendor/ and compiled assets)
//   public_html/    the web root: index.php points at ../jflsolar-app
//   DEPLOY.txt      upload instructions
//
// Extract it in the domain folder (e.g. domains/jflsolar.ph/) so that
// jflsolar-app sits next to public_html. Only public_html is reachable from
// the web; code, .env, storage and media stay outside it.
import { execFileSync } from "node:child_process";
import { cpSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const root = process.cwd();
const php = process.env.PHP_BINARY || "php";
const composer = process.env.COMPOSER_PHAR; // path to composer.phar, or use "composer" on PATH
const stamp = new Date().toISOString().slice(0, 10);
const out = join(root, "release");
const stage = join(out, `jflsolar-release-${stamp}`);
const app = join(stage, "jflsolar-app");
const web = join(stage, "public_html");

const run = (cmd, args, opts = {}) => execFileSync(cmd, args, { stdio: "inherit", ...opts });

const dirty = execFileSync("git", ["status", "--porcelain"], { encoding: "utf8" }).trim();
if (dirty) console.warn("! Working tree has uncommitted changes; the release uses the last commit.\n");
const commit = execFileSync("git", ["rev-parse", "--short", "HEAD"], { encoding: "utf8" }).trim();

console.log("1/5 Building CSS and JavaScript…");
run("npm", ["run", "build"], { shell: true });

console.log("2/5 Exporting the committed code…");
rmSync(stage, { recursive: true, force: true });
mkdirSync(app, { recursive: true });
run("bash", ["-c", `git archive --format=tar HEAD | tar -x -C "${app.replace(/\\/g, "/")}"`]);

console.log("3/5 Installing production PHP components (vendor/)…");
const composerCmd = composer ? [php, [composer]] : ["composer", []];
run(composerCmd[0], [...composerCmd[1], "install", "--no-dev", "--optimize-autoloader", "--no-interaction", "--no-progress", "--no-scripts"], { cwd: app });
run(php, ["artisan", "package:discover", "--ansi"], { cwd: app });

console.log("4/5 Arranging public_html…");
cpSync(join(root, "public", "build"), join(app, "public", "build"), { recursive: true });
cpSync(join(app, "public"), web, { recursive: true });
rmSync(join(app, "public"), { recursive: true, force: true });
const index = readFileSync(join(web, "index.php"), "utf8")
  .replaceAll("__DIR__.'/../../jflsolar-data'", "__DIR__.'/../jflsolar-data'")
  .replaceAll("__DIR__.'/../../jflsolar-app'", "__DIR__.'/../jflsolar-app'")
  .replaceAll("__DIR__.'/../storage'", "__DIR__.'/../jflsolar-app/storage'")
  .replaceAll("__DIR__.'/../vendor/", "__DIR__.'/../jflsolar-app/vendor/")
  .replaceAll("__DIR__.'/../bootstrap/", "__DIR__.'/../jflsolar-app/bootstrap/")
  .replace("$app->handleRequest(", "// The web root is public_html, next to the application folder.\n$app->usePublicPath(__DIR__);\n\n$app->handleRequest(");
if (!index.includes("jflsolar-app/vendor/autoload.php") || !index.includes("usePublicPath")) throw new Error("index.php patch failed");
writeFileSync(join(web, "index.php"), index);
// Things the server never needs.
for (const p of ["tests", "node_modules", ".claude", ".github", "scripts", "phpunit.xml", "vite.config.js", "package.json", "package-lock.json", "resources/css", "resources/js"]) {
  rmSync(join(app, p), { recursive: true, force: true });
}
for (const d of ["storage/app/media", "storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "storage/logs"]) {
  mkdirSync(join(app, d), { recursive: true });
}
writeFileSync(join(app, "RELEASE"), `commit ${commit}\nbuilt ${new Date().toISOString()}\n`);
// First installs import this in phpMyAdmin (no SSH needed). Regenerate it after
// changing migrations: see docs/HOSTINGER.md, "Updating the database structure".
const schema = join(root, "database", "schema", "jflsolar.sql");
if (!existsSync(schema)) throw new Error("database/schema/jflsolar.sql is missing — generate it before releasing (docs/HOSTINGER.md).");
cpSync(schema, join(stage, "database-schema.sql"));
// Existing installs import the upgrade-*.sql files (in date order) instead.
const upgradesDir = join(root, "database", "upgrades");
const upgrades = existsSync(upgradesDir) ? readdirSync(upgradesDir).filter((f) => /^upgrade-\d{4}-\d{2}-\d{2}\.sql$/.test(f)).sort() : [];
for (const u of upgrades) cpSync(join(upgradesDir, u), join(stage, u));
writeFileSync(
  join(stage, "DEPLOY.txt"),
  `JFL Solar website — release ${stamp} (commit ${commit})

Upload with Hostinger File Manager:
1. Open the domain's folder (the one that contains public_html).
2. Upload this zip there and choose Extract. You get jflsolar-app/ next to public_html/.
   - First install: also import database-schema.sql in phpMyAdmin.
   - Updates: keep jflsolar-app/.env and jflsolar-app/storage/ (photos, logs) — do not delete them.
   - Updates: import in phpMyAdmin every upgrade-*.sql newer than your last update (oldest first).
     Each one is safe to import twice. Included: ${upgrades.join(", ") || "none"}.
3. First install only: copy jflsolar-app/.env.example to jflsolar-app/.env and fill it in.
4. PHP version: 8.3 or newer (hPanel → Advanced → PHP Configuration).

Full guide: docs/HOSTINGER.md in the repository.
`,
);

console.log("5/5 Zipping…");
const zip = `${stage}.zip`;
rmSync(zip, { force: true });
// bsdtar writes standard zips with forward-slash paths. (Windows PowerShell's
// Compress-Archive writes backslashes, which Linux hosts extract as file names.)
const tar = process.platform === "win32" ? "C:\\Windows\\System32\\tar.exe" : "bsdtar";
run(tar, ["-a", "-c", "-f", zip, "-C", stage, "."]);
console.log(`\nDone: ${zip}`);
