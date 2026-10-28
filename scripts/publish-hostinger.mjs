// Publishes a deploy-ready copy of this repository to the GitHub repository that
// Hostinger's Git integration deploys from (docs/HOSTINGER.md, section 10).
//
//   node scripts/publish-hostinger.mjs <git-url> [--push]
//
// Hostinger's Git deployment runs no build step and no Composer: the committed files
// are the files served. So each published commit is the source code at HEAD plus
// vendor/ (production PHP components) and public/build/ (compiled CSS/JS).
// Without --push the commit is only prepared in release/hostinger-repo for review.
import { execFileSync } from "node:child_process";
import { cpSync, existsSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const [url, flag] = process.argv.slice(2);
if (!url || (flag && flag !== "--push")) {
  console.error("Usage: node scripts/publish-hostinger.mjs <git-url> [--push]");
  process.exit(1);
}
const root = process.cwd();
const php = process.env.PHP_BINARY || "php";
const composer = process.env.COMPOSER_PHAR; // path to composer.phar, or use "composer" on PATH
const target = join(root, "release", "hostinger-repo");
const run = (cmd, args, opts = {}) => execFileSync(cmd, args, { stdio: "inherit", ...opts });
const out = (cmd, args, opts = {}) => execFileSync(cmd, args, { encoding: "utf8", ...opts }).trim();

if (out("git", ["status", "--porcelain"])) {
  console.error("Commit or stash your changes first: the published copy is built from the last commit.");
  process.exit(1);
}
const commit = out("git", ["rev-parse", "--short", "HEAD"]);
const subject = out("git", ["log", "-1", "--format=%s"]);

console.log("1/5 Building CSS and JavaScript…");
run("npm", ["run", "build"], { shell: true });

console.log("2/5 Fetching the deployment repository…");
if (!existsSync(join(target, ".git"))) {
  rmSync(target, { recursive: true, force: true });
  run("git", ["clone", url, target]);
} else {
  run("git", ["remote", "set-url", "origin", url], { cwd: target });
  run("git", ["fetch", "origin"], { cwd: target });
}
const hasMain = out("git", ["ls-remote", "--heads", "origin", "main"], { cwd: target }) !== "";
const hasLocalMain = out("git", ["branch", "--list", "main"], { cwd: target }) !== "";
if (hasMain) run("git", ["checkout", "-B", "main", "origin/main"], { cwd: target });
// Empty remote: start main once; a prepared-but-unpushed main is reused on the next run.
else if (hasLocalMain) run("git", ["checkout", "main"], { cwd: target });
else run("git", ["checkout", "--orphan", "main"], { cwd: target, stdio: "ignore" });

console.log("3/5 Replacing its files with this commit…");
for (const entry of readdirSync(target)) if (entry !== ".git") rmSync(join(target, entry), { recursive: true, force: true });
run("bash", ["-c", `git archive --format=tar HEAD | tar -x -C "${target.replace(/\\/g, "/")}"`]);

console.log("4/5 Adding vendor/ and public/build/…");
const composerCmd = composer ? [php, [composer]] : ["composer", []];
run(composerCmd[0], [...composerCmd[1], "install", "--no-dev", "--optimize-autoloader", "--no-interaction", "--no-progress", "--no-scripts"], { cwd: target });
cpSync(join(root, "public", "build"), join(target, "public", "build"), { recursive: true });
// Same ignore rules as the source, except that the built files are committed here.
const ignore = readFileSync(join(target, ".gitignore"), "utf8")
  .split("\n")
  .filter((l) => !["/vendor", "/public/build"].includes(l.trim()))
  .join("\n");
writeFileSync(join(target, ".gitignore"), `# Deploy copy: vendor/ and public/build/ are committed (Hostinger runs no build).\n${ignore}`);
for (const must of ["vendor/autoload.php", "public/build/manifest.json", "public/index.php", ".htaccess"]) {
  if (!existsSync(join(target, must))) throw new Error(`Missing ${must} in the deploy copy`);
}

console.log("5/5 Committing…");
run("git", ["add", "-A"], { cwd: target });
if (out("git", ["status", "--porcelain"], { cwd: target })) {
  run("git", ["commit", "-q", "-m", `Deploy ${commit}: ${subject}\n\nBuilt from hunghang03/jflsolar-php@${commit} with vendor/ (no dev packages) and public/build/.`], { cwd: target });
} else {
  console.log("No file changes since the last prepared commit."); // a prepared, unpushed commit is still pushed below
}
const unpushed = hasMain ? out("git", ["rev-list", "--count", "origin/main..main"], { cwd: target }) !== "0" : true;
if (!unpushed) {
  console.log("Nothing new to publish: the deployment repository is already up to date.");
} else if (flag === "--push") {
  run("git", ["push", "origin", "main"], { cwd: target });
  console.log(`\nPublished ${commit} to ${url} (main). Hostinger deploys it on the next (auto-)deployment.`);
} else {
  console.log(`\nPrepared in ${target}. Review it, then run again with --push to publish.`);
}
