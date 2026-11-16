<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Stock sync from the "JFL Solar Inventory List" Google Sheet.
 *
 * A small Apps Script inside the sheet (shown once in Admin → Inventory sync) sends the latest
 * month tab's item names and ON-HAND quantities to POST /webhooks/inventory, with
 * "Authorization: Bearer <secret>". No prices leave the sheet. For each website product linked
 * to one or more sheet rows: on hand above 0 → "Available to order"; 0 or below → "Ask about
 * availability" (a product on pre-order stays on pre-order). Products with no sheet row are
 * left alone. Only the secret's SHA-256 hash is stored.
 */
final class InventorySync
{
    private const SECRET = 'inventory_webhook_hash';

    private const LINKS = 'inventory_links';

    private const LAST = 'inventory_last';

    /** Website product slug => inventory sheet item names (matched ignoring case and extra spaces). */
    public const MAP = [
        'jinko-solar-620w-bifacial-panel' => ['Jinko 620W Bi-Facial Solar Panel'],
        'jinko-solar-650w-bifacial-panel' => ['Jinko 650W Bi-Facial Solar Panel'],
        'seraphim620w-solar-panels' => ['Seraphim Bi-Facial 620W (SRP-620-BTC-BG) Falcon Tang'],
        'ae630w-solar-panels' => ['AE Solar 630W Bi-Facial Photonergy'],
        'goodwe-gw6000-es-c10-hybrid-inverter' => ['GoodWe 6kW Hybrid Inverter'],
        'goodwe-gw8000-es-c10-hybrid-inverter' => ['GoodWe 8kW Hybrid Inverter'],
        'goodwe-gw10000-es-c10-hybrid-inverter' => ['GoodWe 10kW Hybrid Inverter'],
        'goodwe-gw12000-es-c10-hybrid-inverter' => ['GoodWe 12kW Hybrid Inverter'],
        'luxpower-gen2-lb-eu-6k-hybrid-inverter' => ['LuxPower Hybrid Inverter - 6KW (GEN2-LB-EU-6K)'],
        'luxpower-gen-lb-eu-8k-hybrid-inverter' => ['LuxPower Hybrid Inverter - 8KW (GEN-LB-EU-8K)'],
        'luxpower-gen-lb-eu-10k-hybrid-inverter' => ['LuxPower Hybrid Inverter - 10KW (GEN-LB-EU-10K)'],
        'luxpower-gen2-lb-eu-12k-hybrid-inverter' => ['LuxPower Hybrid Inverter - 12KW (LXP-LB-EU-12K) Gen2'],
        'luxpower-gen2-lb-eu-14k-hybrid-inverter' => ['LuxPower Hybrid Inverter - 14KW (LXP-LB-EU-14K) Gen2'],
        'luxpower-sna-pro-eu-6-5k-off-grid-inverter' => ['LuxPower Off-grid Inverter - 6.5KW SNA Pro IP65'],
        'luxpower-sna-12k-off-grid-inverter' => ['LuxPower Off-grid Inverter - 12KW (LXP-SNA-12000WPV)'],
        'solis-s6-eh1p6k-l-plus-hybrid-inverter' => ['Solis 6kW Hybrid Inverter'],
        'solis-s6-eh1p8k-l-plus-hybrid-inverter' => ['Solis 8kW Hybrid Inverter'],
        'solis-s6-eh1p12k03-hybrid-inverter' => ['Solis 12kW Hybrid Inverter'],
        'solis-s6-eh1p16k03-hybrid-inverter' => ['Solis 16kW Hybrid Inverter'],
        'alltopelec-16-5kwh-battery' => ['Alltopelec 51.2V 324Ah'],
        'dyness-314ah-powerbrick-sc' => ['Dyness 314AH Powerbrick SC (IP20)'],
        'dyness-314ah-powerbrick-max' => ['Dyness 314AH Powerbrick MAX (IP20)'],
        '6mm2-pv-cable-single-core-red' => ['PV Cable - 1*6mm² Red 500m'],
        '6mm2-pv-cable-single-core' => ['PV Cable - 1*6mm² Black 500m'],
        '2x6mm2-pv-cable-twin-core' => ['PV Cable 2*6mm² Twin core 200m/roll'],
        '35mm2-battery-cable' => ['Battery Cable 35mm² Black', 'Battery Cable 35mm² Red'],
        '50mm2-battery-cable-black' => ['Battery Cable 50mm² Black', 'Battery Cable 50mm² Black (Cipriano)'],
        'railing' => ['Railings 2.4m', 'Railing 2.4meters (ATE)'],
        'l-foot' => ['L-Foot (Long)', 'L-Foot', 'L-Foot (ATE)'],
        'end-clamp' => ['End Clamp', 'End Clamp (ATE)'],
        'mid-clamp' => ['Middle Clamp', 'Middle Clamp (ATE)', 'Mid Clamp'],
        'ground-lug' => ['Ground Lugs'],
        'splice-connector' => ['Splice Connector', 'Splice Connector (ATE)'],
        'taxnele-2p-63a-residential-ats' => ['ATS 63A Taxnele'],
        'taxnele-2p-125a-residential-ats' => ['ATS 125A Taxnelle'],
        'tomzn-2p-125a-residential-ats' => ['ATS 125A TOMZN'],
        'industrial-ats-125a' => ['Industrial ATS 125A'],
        'ichyti-ats-63a' => ['Ichyti ATS 63A'],
        'ichyti-ats-125a' => ['Ichyti ATS 125A'],
        'rnm-4p-250a-industrial-ats' => ['RNM 4P Industrial ATS 250A'],
        'rnm-4p-400a-industrial-ats' => ['RNM 4P Industrial ATS 400A'],
        'feeo-fe7-63-2p-32a-ac-mcb' => ['Breaker AC 2P 32A (FEEO)'],
        'chint-ac-mcb-2p-63a' => ['Breaker AC 2P 63A CHINT'],
        'chint-ac-breaker-2p-100a' => ['Breaker AC 2P 100A CHINT'],
        'leikixing-ac-spd-2p' => ['Breaker AC 2P SPD (Leikixing)'],
        'projoy-ac-spd-2p' => ['Breaker AC 2P SPD Projoy'],
        'feeo-ac-spd-2p' => ['Breaker AC 2P SPD FEEO'],
        'himel-ac-mccb-100a' => ['Breaker AC MCCB 100A Himel'],
        'himel-ac-mccb-125a' => ['Breaker AC MCCB 125A Himel'],
        'ac-isolator-switch-4p-63a-415v' => ['Breaker AC 4P Isolator Switch 415V 63A'],
        'leikeking-2p-20a-dc-breaker' => ['Breaker DC 2P 20A Leikeking'],
        'leikeking-2p-25a-dc-breaker' => ['Breaker DC 2P 25A Leikeking'],
        'feeo-fpv-63-2p-20a-dc-mcb' => ['Breaker DC 2P 20A, 550V (FEEO)'],
        'suntree-dc-mcb-2p-20a-550v' => ['Breaker DC 2P 20A, 550V Suntree'],
        'etek-ekm1-63dc-2p-40a-dc-mcb' => ['Breaker DC 2P 40A ET3K'],
        'dc-spd-20-40ka-600v' => ['Breaker DC SPD 20-40KA, 600V', 'Breaker DC SPD 20-40KA, 600V (FEEO)', 'Breaker DC SPD 20-40KA, 600V Tomzn'],
        'feeo-dc-mccb-2p-160a' => ['Breaker DC 2P MCCB - 100V 160A FEEO'],
        'feeo-fpvm-250-2p-250a-dc-mccb' => ['Breaker DC 2P MCCB - 100V 250A FEEO'],
        'qcd-dc-mccb-2p-200a' => ['Breaker DC 2P MCCB 200A QCD'],
        'rnm-dc-mccb-2p-250a' => ['Breaker DC 2P MCCB 250A RNM'],
        'taxnele-dc-mccb-2p-160a' => ['Breaker DC 2P MCCB 160A TAXNELLE'],
        'taxnele-dc-mccb-2p-200a' => ['Breaker DC 2P MCCB 200A TAXNELLE'],
        'projoy-2-strings-rsd' => ['Projoy RSD - 2 Strings'],
        'projoy-4-strings-rsd' => ['Projoy RSD - 4 Strings'],
        'projoy-8-strings-rsd' => ['Projoy RSD - 8 Strings'],
        'rsd-push-button-switch' => ['RSD Push Botton Switch', 'RSD Push Button Switch'],
        'grounding-rod' => ['Grounding Rod'],
        'ground-clamp' => ['Ground Clamp'],
        'solderless-connector-50mm' => ['Solderless Connector 50mm'],
        'solderless-connector-38mm' => ['Solderless Connector 38mm'],
        'solderless-connector-30mm' => ['Solderless Connector 30mm'],
        'solderless-connector-22mm' => ['Solderless Connector 22mm'],
        'mc4-connector-pair' => ['MC4 Positive and Negative Connectors Pair'],
        'distribution-box-12-ways' => ['Distribution Box 12ways'],
        'flexible-conduit-25mm-100m' => ['Flexible Conduit 25mm 100M'],
        'flexible-conduit-32mm-100m' => ['Flexible Conduit 32mm 100M'],
        'flexible-conduit-40mm-100m' => ['Flexible Conduit 40mm 100M'],
        'flexible-conduit-40mm-200m' => ['Flexible Conduit 40mm 200M (Gelan Solar)'],
        'flexible-conduit-hdpe-25mm-male-adapter' => ['Flexible Conduit HDPE 25mm Male Adapter'],
        'flexible-conduit-hdpe-32mm-male-adapter' => ['Flexible Conduit HDPE 32mm Male Adapter'],
        'flexible-conduit-hdpe-40mm-male-adapter' => ['Flexible Conduit HDPE 40mm Male Adapter'],
        'flexible-conduit-hdpe-40mm-coupler' => ['Flexible Conduit HDPE 40mm Coupler'],
        'flexible-conduit-hdpe-32mm-coupler' => ['Flexible Conduit HDPE 32mm Coupler'],
        'battery-lug-sc50-10' => ['Battery Lugs SC50-10mm'],
        'battery-lug-sc50-8' => ['Battery Lugs SC50-8mm'],
        'battery-lug-sc35-10' => ['Battery Lugs SC35-10mm'],
        'battery-lug-sc35-8' => ['Battery Lugs SC35-8mm'],
        'battery-lug-sc25-10' => ['Battery Lugs SC25-10mm'],
        'battery-lug-sc25-8' => ['Battery Lugs SC25-8mm'],
        'dtlc-terminal-lug-25mm' => ['DTLC Terminal Lug 25mm'],
        'dtlc-terminal-lug-35mm' => ['DTLC Terminal Lug 35mm'],
        'cable-gland-pg19-white' => ['Cable Gland PG19 (White)'],
        'cable-gland-pg19-black' => ['Cable Gland PG19 (Black)'],
        'cable-gland-pg21-black' => ['Cable Gland PG21 (Black)'],
        'cable-gland-pg25-black' => ['Cable Gland PG25 (Black)'],
        'cable-gland-pg29-black' => ['Cable Gland PG29 (Black)'],
        'powder-coated-cable-tray-100x150mm' => ['Powder Coated Cable Tray 100*150mm'],
        'metal-cable-tray-50x100x2000mm' => ['Metal Cable Tray 50*100*2000mm'],
        'horizontal-elbow-50x100mm' => ['Horizontal Elbow 50*100mm'],
        'metal-enclosure-200x500x800mm' => ['Metal Enclosure 200mm* 500mm*800mm'],
        'metal-enclosure-200x500x500mm' => ['Metal Enclosure 200mm*500mm*500mm'],
        'hinged-type-cable-tray-140x150x2000mm' => ['Hinged Type Cable Tray 140*150*2000mm'],
        'hinged-type-cable-tray-140x150x1200mm' => ['Hinged Type Cable Tray 140*150*1200mm'],
        'pull-box-150x400x500mm' => ['Pull Box 150x400x500mm'],
        'pull-box-150x300x300mm' => ['Pull Box 150x300x300mm'],
        'saddle-clamp-25mm' => ['Saddle Clamp 3/4" (25mm)'],
        'saddle-clamp-32mm' => ['Saddle Clamp 1" (32mm)'],
        'saddle-clamp-40mm' => ['Saddle Clamp 1 .2" (40mm)', 'Saddle Clamp 1-1/2" (40mm)'],
        'metal-din-rail-1m' => ['Metal Din Rail (1m)'],
        'din-rail-stopper' => ['Din Rail Stopper'],
        'silicone-sealant-black' => ['Silicone Sealant (Black)'],
        'cable-tie-black-5mm-400mm' => ['Cable Tie (Black) 5mmx400mm'],
        'cable-tie-black-4mm-300mm' => ['Cable Tie (Black) 4mmx300mm'],
        'cold-pressed-wire-terminal-10mm' => ['Cold Pressed Wire Terminal 10mm'],
        'cold-pressed-wire-terminal-16mm' => ['Cold Pressed Wire Terminal 16mm'],
        'cold-pressed-wire-terminal-25mm' => ['Cold Pressed Wire Terminal 25mm'],
        'plastic-moulding-50x100mm' => ['Plastic Moulding 50 by 100mm'],
        'plastic-moulding-50x100mm-coupler' => ['Plastic Moulding 50 by 100mm Coupler'],
        'plastic-moulding-50x100mm-tee' => ['Plastic Moulding 50 by 100mm Tee'],
        'plastic-moulding-50x100mm-elbow' => ['Plastic Moulding 50 by 100mm Elbow'],
        'plastic-moulding-50x100mm-end-cap' => ['Plastic Moulding 50 by 100mm End Cap'],
    ];

    public static function norm(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $name)));
    }

    // --- Secret (same storage as the mail webhook) ---------------------------------------

    public static function configured(): bool
    {
        return self::get(self::SECRET) !== null;
    }

    /** Make a new secret; returns it once (only its hash is kept). */
    public static function newSecret(): string
    {
        $secret = bin2hex(random_bytes(24));
        self::put(self::SECRET, hash('sha256', $secret));

        return $secret;
    }

    public static function forgetSecret(): void
    {
        InboundMail::ensureTable();
        DB::table('app_secrets')->where('key', self::SECRET)->delete();
    }

    public static function authorized(?string $authorization): bool
    {
        $hash = self::get(self::SECRET);

        return $hash && preg_match('/^Bearer\s+(\S+)$/i', trim((string) $authorization), $m)
            && hash_equals($hash, hash('sha256', $m[1]));
    }

    // --- Links between sheet rows and products ---------------------------------------------

    /** Extra links staff made in the admin: normalised sheet name => product slug. */
    public static function customLinks(): array
    {
        $links = json_decode((string) self::get(self::LINKS), true);

        return is_array($links) ? $links : [];
    }

    public static function link(string $sheetName, ?string $slug): void
    {
        $links = self::customLinks();
        $key = self::norm($sheetName);
        if ($slug) {
            $links[$key] = $slug;
        } else {
            unset($links[$key]);
        }
        self::put(self::LINKS, json_encode($links, JSON_UNESCAPED_UNICODE));
    }

    /** product slug => list of normalised sheet names. */
    public static function links(): array
    {
        $out = [];
        foreach (self::MAP as $slug => $names) {
            foreach ($names as $n) {
                $out[$slug][] = self::norm($n);
            }
        }
        foreach (self::customLinks() as $name => $slug) {
            if ($slug === '-') {                       // "not on the website"
                continue;
            }
            foreach ($out as $s => $names) {
                $out[$s] = array_values(array_diff($names, [$name]));
            }
            $out[$slug][] = $name;
        }

        return array_filter($out);
    }

    // --- Applying a sync -----------------------------------------------------------------

    /**
     * @param  array<int, mixed>  $items  [{name, qty}]
     * @return array<string, mixed> the report also shown in the admin
     */
    public static function apply(array $items, string $sheet): array
    {
        $qty = [];
        $shown = [];
        foreach (array_slice($items, 0, 3000) as $it) {
            if (! is_array($it) || ! is_string($it['name'] ?? null) || ! is_numeric($it['qty'] ?? null)) {
                continue;
            }
            $key = self::norm(mb_substr($it['name'], 0, 200));
            if ($key === '') {
                continue;
            }
            $qty[$key] = ($qty[$key] ?? 0) + (float) $it['qty'];
            $shown[$key] ??= trim((string) preg_replace('/\s+/u', ' ', $it['name']));
        }

        $links = self::links();
        $used = [];
        $changed = [];
        $linked = [];
        $products = Product::query()->whereIn('slug', array_keys($links))->get()->keyBy('slug');
        foreach ($links as $slug => $names) {
            $present = array_values(array_filter($names, fn ($n) => array_key_exists($n, $qty)));
            $p = $products[$slug] ?? null;
            if (! $p || $present === []) {
                continue;
            }
            $onHand = array_sum(array_map(fn ($n) => $qty[$n], $present));
            $used = array_merge($used, $present);
            $new = $onHand > 0 ? 'available' : ($p->availability === 'preorder' ? 'preorder' : 'on_request');
            $linked[] = ['slug' => $slug, 'name' => $p->name, 'brand' => $p->brand, 'qty' => $onHand, 'status' => $new];
            if ($p->availability !== $new) {
                $changed[] = ['slug' => $slug, 'name' => $p->name, 'brand' => $p->brand, 'from' => $p->availability, 'to' => $new, 'qty' => $onHand];
                $p->availability = $new;
                $p->save();
            }
        }
        $ignored = array_keys(array_filter(self::customLinks(), fn ($s) => $s === '-'));
        $unmatched = [];
        foreach ($qty as $key => $q) {
            if (! in_array($key, $used, true) && ! in_array($key, $ignored, true)) {
                $unmatched[] = ['name' => $shown[$key], 'qty' => $q];
            }
        }
        if ($changed) {
            PublicContent::flush();
        }
        $report = [
            'at' => now()->toIso8601String(),
            'sheet' => mb_substr($sheet, 0, 60),
            'rows' => count($qty),
            'changed' => $changed,
            'linked' => $linked,
            'unmatched' => $unmatched,
        ];
        self::put(self::LAST, json_encode($report, JSON_UNESCAPED_UNICODE));

        return $report;
    }

    public static function last(): ?array
    {
        $r = json_decode((string) self::get(self::LAST), true);

        return is_array($r) ? $r : null;
    }

    /** The Apps Script staff paste into the sheet (Extensions → Apps Script). */
    public static function script(string $secret): string
    {
        $url = Site::url('/webhooks/inventory');

        return <<<JS
// JFL Solar: sends the stock on hand (item name + quantity only, no prices) to the website.
// Paste into Extensions → Apps Script, save, then run "setup" once and click Allow.
const JFL_URL = '{$url}';
const JFL_SECRET = '{$secret}';

function onOpen() {
  SpreadsheetApp.getUi().createMenu('JFL Solar').addItem('Sync website stock now', 'syncWebsite').addToUi();
}

// Run once: syncs every hour and shortly after edits.
function setup() {
  ScriptApp.getProjectTriggers().forEach(function (t) { ScriptApp.deleteTrigger(t); });
  ScriptApp.newTrigger('syncWebsite').timeBased().everyHours(1).create();
  ScriptApp.newTrigger('syncOnEdit').forSpreadsheet(SpreadsheetApp.getActive()).onEdit().create();
  syncWebsite();
}

function syncOnEdit() {
  var last = Number(PropertiesService.getScriptProperties().getProperty('jflLast') || 0);
  if (Date.now() - last > 60000) syncWebsite();
}

// The newest month tab, e.g. "Nov 2026", "Sept 2026" or "January 2027".
function latestMonthSheet_() {
  var months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'], best = null, bestKey = -1;
  SpreadsheetApp.getActive().getSheets().forEach(function (sh) {
    var m = sh.getName().toLowerCase().match(/([a-z]+)\.?\s+(\d{4})/);
    var i = m ? months.indexOf(m[1].slice(0, 3)) : -1;
    if (i >= 0 && Number(m[2]) * 12 + i > bestKey) { bestKey = Number(m[2]) * 12 + i; best = sh; }
  });
  return best;
}

function syncWebsite() {
  var sh = latestMonthSheet_();
  if (!sh) throw new Error('No month tab found (e.g. "Nov 2026").');
  var values = sh.getDataRange().getDisplayValues(), col = -1;
  for (var r = 0; r < Math.min(8, values.length) && col < 0; r++) {
    col = values[r].findIndex(function (v) { return /on-?\s*hand/i.test(v); });
  }
  if (col < 0) throw new Error('No ON-HAND column in ' + sh.getName());
  var items = [];
  values.forEach(function (row) {
    var name = String(row[2] || '').trim(), q = String(row[col] || '').replace(/[,\s]/g, '');
    if (name && q !== '' && !isNaN(Number(q))) items.push({ name: name, qty: Number(q) });
  });
  PropertiesService.getScriptProperties().setProperty('jflLast', String(Date.now()));
  var res = UrlFetchApp.fetch(JFL_URL, {
    method: 'post', contentType: 'application/json', muteHttpExceptions: true,
    headers: { Authorization: 'Bearer ' + JFL_SECRET },
    payload: JSON.stringify({ sheet: sh.getName(), items: items })
  });
  if (res.getResponseCode() !== 200) throw new Error('Website answered ' + res.getResponseCode() + ': ' + res.getContentText().slice(0, 200));
  return res.getContentText();
}
JS;
    }

    // --- storage ---------------------------------------------------------------------------

    private static function get(string $key): ?string
    {
        InboundMail::ensureTable();

        return DB::table('app_secrets')->where('key', $key)->value('value');
    }

    private static function put(string $key, string $value): void
    {
        InboundMail::ensureTable();
        DB::table('app_secrets')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
    }
}
