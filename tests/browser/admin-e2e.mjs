// End-to-end admin test for the Laravel edition (local, test data only).
import puppeteer from "puppeteer-core";
import { readFileSync } from "node:fs";

const [base, phraseFile, imgPath, pdfPath] = process.argv.slice(2);
const phrase = readFileSync(phraseFile, "utf8").trim();
const results = [];
const check = (name, ok, detail = "") => {
  results.push({ name, ok, detail });
  console.log(`${ok ? "PASS" : "FAIL"} ${name}${detail ? " — " + detail : ""}`);
};
const browser = await puppeteer.launch({ protocolTimeout: 60000, executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe", headless: true, args: ["--no-sandbox"] });
const page = await browser.newPage();
await page.setViewport({ width: 1280, height: 900 });
page.on("dialog", (d) => d.accept());
const errors = [];
page.on("pageerror", (e) => errors.push(e.message));
const go = async (path) => {
  const r = await page.goto(base + path, { waitUntil: "load" });
  return r.status();
};
const text = () => page.evaluate(() => document.body.innerText);
const submit = async (selector) => {
  await Promise.all([page.waitForNavigation({ waitUntil: "load" }), page.$eval(selector, (el) => (el.tagName === "FORM" ? el.requestSubmit() : el.click()))]);
};
const fill = async (sel, value) => {
  await page.$eval(sel, (el, v) => {
    el.value = v;
    el.dispatchEvent(new Event("input", { bubbles: true }));
  }, value);
};

// 1. First owner through recovery
await go("/admin/login");
check("login shows no-accounts notice", (await text()).includes("No staff accounts yet"));
await go("/admin/recover");
await fill("#phrase", "wrong-phrase-entirely");
await fill("#name", "Test Owner");
await fill("#email", "owner@jflsolar.test");
await fill("#password", "green panels on the roof");
await fill("#password_confirmation", "green panels on the roof");
await submit("form[action='/admin/recover']");
check("wrong recovery phrase rejected", (await text()).includes("does not match the phrase"));
await fill("#phrase", phrase);
await fill("#password", "green panels on the roof");
await fill("#password_confirmation", "green panels on the roof");
await submit("form[action='/admin/recover']");
check("first owner created", (await text()).includes("Owner account created"));

// 2. Sign in
await fill("#password", "wrong password here");
await submit("form[action='/admin/login']");
check("wrong password rejected", (await text()).includes("do not match an active staff account"));
await fill("#email", "owner@jflsolar.test");
await fill("#password", "green panels on the roof");
await submit("form[action='/admin/login']");
check("signed in to overview", page.url().endsWith("/admin") && (await text()).includes("Before launch"));

// 3. Packages: list, edit validation, save, concurrency
await go("/admin/packages");
const firstEdit = await page.$eval("main ul li h2 a", (a) => a.getAttribute("href"));
check("packages list has items", Boolean(firstEdit));
await go(firstEdit);
await fill("#f-name", "");
await fill("#f-price_php", "abc");
await page.select("#f-pricing_mode", "fixed");
await submit("button[name=intent][value=save]");
let t = await text();
check("validation errors shown and summary focused", t.includes("Please fix the highlighted fields") && t.includes("Name is required") && (await page.evaluate(() => document.activeElement?.getAttribute("role"))) === "alert");
check("typed values restored after error", (await page.$eval("#f-price_php", (e) => e.value)) === "abc");
await fill("#f-name", "Trio Package — edited in test");
await fill("#f-price_php", "199000");
await submit("button[name=intent][value=save]");
t = await text();
check("package saved", t.includes("Changes saved") && (await page.$eval("#f-name", (e) => e.value)) === "Trio Package — edited in test");
const editUrl = page.url().split("?")[0];
// concurrency: submit stale lock_version
await page.$eval("input[name=lock_version]", (e) => (e.value = "0"));
await submit("button[name=intent][value=save]");
check("stale save rejected", (await text()).includes("Someone else saved this item"));

// 4. Status lifecycle from the edit page
await go(editUrl.replace(base, ""));
const status0 = await page.$eval("section[aria-label=Status] span", (e) => e.textContent.trim());
check("status shown", ["draft", "published"].includes(status0), status0);
if (status0 === "published") {
  await page.click("section[aria-label=Status] button[data-open-dialog]"); // Unpublish (first confirm)
  await submit("dialog[open] button[type=submit]");
  check("unpublished", (await text()).includes("Unpublished"));
}
await submit("section[aria-label=Status] form button[type=submit]"); // Publish (draft → published)
check("published", (await text()).includes("Published. It is now visible"));

// 5. Images: upload, details, cover, delete
const upload = await page.$("#upload-file");
await upload.uploadFile(imgPath);
await page.waitForFunction(() => !document.querySelector("#upload-file + p")?.textContent);
await fill("#upload-alt", "Rooftop solar array used for testing");
await submit("form[action*='/admin/media/upload/'] button[type=submit]");
t = await text();
check("image uploaded", t.includes("Image uploaded"));
const imgSrc = await page.$eval("#images ol img", (i) => i.getAttribute("src"));
const adminImg = await page.evaluate(async (s) => (await fetch(s)).status, imgSrc);
check("admin image served", adminImg === 200, imgSrc);
const publicSrc = imgSrc.replace("/admin/media/", "/media/");
const pubStatus = await page.evaluate(async (s) => (await fetch(s, { credentials: "omit", cache: "no-store" })).status, publicSrc);
check("public image served while published", pubStatus === 200);
await fill("#images ol input[name=alt]", "Updated alt text for test");
const unsavedVisible = await page.$eval("#images [data-unsaved-note]", (e) => !e.hidden);
check("image details show unsaved note", unsavedVisible);
await submit("#images ol form[data-image-text] button[type=submit]");
check("image details saved", (await text()).includes("Image details saved"));
// A rejected replacement keeps its panel open with the error tied to the file input.
await page.$eval("#images ol details", (d) => { d.open = true; d.querySelector("input[type=file]").removeAttribute("data-prepare-image"); });
await (await page.$("#images ol details input[type=file]")).uploadFile(pdfPath);
await submit("#images ol details form button[type=submit]");
const repState = await page.$eval("#images ol details", (d) => {
  const input = d.querySelector("input[type=file]");
  const err = document.getElementById(input.getAttribute("aria-describedby") || "-");
  return d.open && input.getAttribute("aria-invalid") === "true" && !!err?.textContent.trim();
});
check("failed replace shows inline error", repState);

// 6. Public page reflects the change
const pubPage = await browser.newPage();
const slug = await page.$eval("#f-slug", (e) => e.value);
const pubRes = await pubPage.goto(base + "/packages/" + slug, { waitUntil: "load" });
check("public package page live", pubRes.status() === 200 && (await pubPage.evaluate(() => document.body.innerText)).includes("Trio Package — edited in test"));
await pubPage.close();
await page.bringToFront();

// 7. Archive then delete permanently
await page.click("section[aria-label=Status] button[data-open-dialog]"); // Unpublish
await submit("dialog[open] button[type=submit]");
const unpubImg = await page.evaluate(async (s) => (await fetch(s, { credentials: "omit", cache: "no-store" })).status, publicSrc);
check("public image hidden after unpublish", unpubImg === 404);
const archiveBtn = await page.$$("section[aria-label=Status] button[data-open-dialog]");
await archiveBtn[archiveBtn.length - 1].click(); // Archive
await submit("dialog[open] button[type=submit]");
check("archived (back on list)", (await text()).includes("Archived. It is hidden"));
await go(editUrl.replace(base, ""));
const delBtns = await page.$$("section[aria-label=Status] button[data-open-dialog]");
await delBtns[delBtns.length - 1].click(); // Delete permanently
await submit("dialog[open] button[type=submit]");
check("deleted permanently", (await text()).includes("Permanently deleted"));
const goneStatus = await page.evaluate(async (s) => (await fetch(s)).status, imgSrc);
check("image files removed with the package", goneStatus === 404);

// 8. Products: search persists into editor back link; datasheet upload
await go("/admin/products?q=goodwe");
const pEdit = await page.$eval("main ul li h2 a", (a) => a.getAttribute("href"));
check("product search keeps back link", pEdit.includes("back=%2Fadmin%2Fproducts%3Fq%3Dgoodwe"), pEdit);
await go(pEdit);
check("editor shows back to search", (await text()).includes("Back to your product search"));
const ds = await page.$("#ds-file");
await ds.uploadFile(pdfPath);
await submit("form[action$='/datasheet'] button[type=submit]");
check("datasheet uploaded", await page.$eval("#datasheet", (s) => s.innerText.includes("Datasheet uploaded")));
await (await page.$("#ds-file")).uploadFile(imgPath);
await submit("form[action$='/datasheet'] button[type=submit]");
check("datasheet error beside its input", await page.$eval("#ds-file", (i) => i.getAttribute("aria-invalid") === "true" && !!document.getElementById("ds-file-error")?.textContent.trim()));
// featured toggle + move on list
await go("/admin/products");
await submit("main ul li form[action$='/featured'] button");
check("featured toggled", /Marked as featured|Removed from featured/.test(await text()));
await submit("main ul li:nth-child(2) form[action$='/move'] button:not([disabled])");
check("reordered", (await text()).includes("Order updated"));

// 8b. Page text: edit a heading, see it on the public page, restore the original
await go("/admin/text");
check("page text opens on the Home tab", page.url().endsWith("/admin/text/home") && (await text()).includes("Top of the page (hero)"));
const heroField = "#f-home__hero__title";
const heroOriginal = await page.$eval(heroField, (e) => e.value);
await fill(heroField, "Solar power for the test run");
await submit("form[action$='/admin/text/home'] button[name=intent]");
check("page text saved", (await text()).includes("Saved. The Home page shows the new wording."));
const homeHtml = await page.evaluate(async () => (await fetch("/", { cache: "no-store" })).text());
check("public home shows the edited heading", homeHtml.includes("Solar power for the test run"));
check("edited field shows the original", (await page.$eval("#f-home__hero__title-hint", (e) => e.textContent)).includes(heroOriginal));
await fill(heroField, "");
await submit("form[action$='/admin/text/home'] button[name=intent]");
check("cleared field restores the original", (await page.$eval(heroField, (e) => e.value)) === heroOriginal);

// 9. Content block, settings
await go("/admin/content");
const blockHref = await page.$eval("main ul a[href^='/admin/content/']", (a) => a.getAttribute("href"));
await go(blockHref);
await fill("#f-body", "Edited body text for the test.\n\nSecond paragraph.");
await submit("button[name=intent][value=save]");
t = await text(); check("content block saved", t.includes("Saved."), t.includes("Saved.") ? "" : page.url() + " :: " + t.slice(0, 600).replace(/\n+/g, " | "));
await go("/admin/settings");
await fill("#f-phone_e164", "0917");
await submit("button[name=intent][value=save]");
check("settings validation", (await text()).includes("use international format"));
await fill("#f-phone_e164", "+639694712098");
await submit("button[name=intent][value=save]");
check("settings saved", (await text()).includes("Settings saved"));

// 10. Staff: add editor, temp password, forced change
await go("/admin/staff");
await fill("#s-name", "Test Editor");
await fill("#s-email", "editor@jflsolar.test");
await submit("form[action='/admin/staff'] button[type=submit]");
t = await text();
const temp = await page.$eval(".select-all", (e) => e.textContent.trim()).catch(() => null);
check("editor added with temporary password", Boolean(temp) && t.includes("Account created"), temp ?? "");

// 11. Inquiries visible (submit one publicly first)
const pub = await browser.newPage();
await pub.goto(base + "/contact", { waitUntil: "load" });
await new Promise((r) => setTimeout(r, 3500));
await pub.$eval("#f-name", (e) => (e.value = "Test Customer"));
await pub.$eval("#f-phone", (e) => (e.value = "0917 123 4567"));
await pub.select("#f-type", "residential");
await pub.$eval("#f-message", (e) => (e.value = "Testing the inquiry inbox from the e2e test."));
await Promise.all([pub.waitForNavigation({ waitUntil: "load" }), pub.$eval("form[data-inquiry-form]", (f) => f.requestSubmit())]);
check("public inquiry accepted", (await pub.evaluate(() => document.body.innerText)).includes("your inquiry was received"));
await pub.close();
await page.bringToFront();
await go("/admin/inquiries");
check("inquiry listed in admin", (await text()).includes("Test Customer"));
await go(await page.$eval("main ul li a", (a) => a.getAttribute("href")));
await submit("form[action^='/admin/inquiries/'] input[value=handled] ~ button"); // Mark handled
check("inquiry marked handled", (await text()).includes("Marked as handled"));

// 12. Sign out, then editor first sign-in forces password change
await submit("form[action='/admin/logout'] button");
await go("/admin/login");
await fill("#email", "editor@jflsolar.test");
await fill("#password", temp);
await submit("form[action='/admin/login']");
check("editor forced to choose password", page.url().endsWith("/admin/password"));
await go("/admin/packages");
check("editor cannot skip password change", page.url().endsWith("/admin/password"));
await fill("#password", "blue river under the bridge");
await fill("#password_confirmation", "blue river under the bridge");
await submit("form[action='/admin/password']");
check("editor password changed", (await text()).includes("Your password has been changed"));
await go("/admin/staff");
check("editor blocked from staff page", (await text()).includes("available to owners only"));

check("no JavaScript errors", errors.length === 0, errors.join(" | "));
await browser.close();
const failed = results.filter((r) => !r.ok);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
