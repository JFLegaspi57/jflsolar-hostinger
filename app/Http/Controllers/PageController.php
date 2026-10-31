<?php

namespace App\Http\Controllers;

use App\Support\Catalog;
use App\Support\Compare;
use App\Support\PublicContent;
use App\Support\Site;
use App\Support\SolarCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $s = PublicContent::snapshot();
        $featured = $s['packages']->where('featured', true);

        return view('pages.home', [
            'settings' => $s['settings'],
            'categories' => $s['categories'],
            'products' => $s['products'],
            'featuredPackages' => ($featured->isNotEmpty() ? $featured : $s['packages'])->take(3),
            'featuredProjects' => $s['projects']->where('featured', true)->take(3),
            'why' => $s['blocks']->where('section', 'why')->values(),
        ]);
    }

    public function services(): View
    {
        return view('pages.services', ['settings' => PublicContent::settings()]);
    }

    public function about(): View
    {
        $s = PublicContent::snapshot();

        return view('pages.about', ['settings' => $s['settings'], 'blocks' => $s['blocks'], 'blockImages' => $s['blockImages']]);
    }

    public function calculator(Request $request): View
    {
        $in = SolarCalculator::input($request->query());
        $submitted = $request->query('type') !== null;
        $result = $submitted ? SolarCalculator::calculate($in) : null;

        return view('pages.calculator', [
            'in' => $in,
            'result' => $result,
            'price' => $result ? SolarCalculator::priceRange($in, $result, PublicContent::snapshot()['packages']) : null,
            'submitted' => $submitted,
            'buildUrl' => $result && ($preset = \App\Support\PackageBuilder::presetFromCalculator($in, $result))
                ? \App\Support\PackageBuilder::presetUrl($preset, ['kwp' => round($result['kwp'], 2), 'type' => $in['type']]) : null,
        ]);
    }

    public function packages(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $type = $request->query('type');
        // Old addresses of the "Complete Packages" and "Materials Only" filters.
        if (in_array($type, ['installed', 'equipment'], true)) {
            return redirect('/packages?type='.($type === 'installed' ? 'complete' : 'materials'), 301);
        }
        $active = in_array($type, ['complete', 'materials'], true) ? $type : 'all';
        $shown = PublicContent::snapshot()['packages']->filter(fn ($p) => $active === 'all'
            || ($active === 'complete' ? $p->package_type === 'installed_system' : $p->package_type === 'equipment_bundle'))->values();

        return view('pages.packages', ['active' => $active, 'shown' => $shown]);
    }

    public function compare(Request $request): View
    {
        // The compare form submits one "p" per ticked checkbox (?p=a&p=b). PHP keeps
        // only the last repeated key, so read every "p" from the raw query string
        // (also accepts p[]=… and a comma-separated list).
        $values = [];
        foreach (explode('&', (string) $request->server('QUERY_STRING')) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            if (in_array(urldecode($k), ['p', 'p[]'], true)) {
                array_push($values, ...explode(',', urldecode($v)));
            }
        }
        $requested = collect($values)->map(fn ($s) => mb_substr(trim($s), 0, 80))->filter()->unique()->values();
        $packages = PublicContent::snapshot()['packages'];
        $selected = $requested->map(fn ($slug) => $packages->firstWhere('slug', $slug))->filter()->values();

        return view('pages.compare', [
            'shown' => $selected->take(Compare::MAX),
            'truncated' => $selected->count() > Compare::MAX,
            'missing' => $requested->count() - $selected->count(),
        ]);
    }

    public function buildYourOwn(Request $request): View
    {
        $preset = \App\Support\PackageBuilder::presetFromQuery($request->query());
        $fromCalc = $preset && is_string($request->query('kwp')) && is_numeric($request->query('kwp'))
            ? ['kwp' => (float) $request->query('kwp'), 'type' => (string) $request->query('type', '')] : null;

        return view('pages.build-your-own', ['lines' => \App\Support\PackageBuilder::customLines($preset), 'settings' => PublicContent::settings(), 'fromCalc' => $fromCalc]);
    }

    public function package(string $slug): View
    {
        $pkg = PublicContent::package($slug) ?? abort(404);

        return view('pages.package', ['pkg' => $pkg, 'settings' => PublicContent::settings()]);
    }

    public function products(Request $request): View
    {
        $s = PublicContent::snapshot();
        $filters = Catalog::filters($request);

        return view('pages.products', [
            'filters' => $filters,
            'categories' => $s['categories'],
            'brands' => Catalog::brands($s['products']),
            'results' => Catalog::filter($s['products'], $filters),
            'activeCategory' => $s['categories']->firstWhere('slug', $filters['category']),
        ]);
    }

    public function product(string $slug): View
    {
        $product = PublicContent::product($slug) ?? abort(404);

        return view('pages.product', ['product' => $product, 'settings' => PublicContent::settings()]);
    }

    public function projects(): View
    {
        return view('pages.projects', ['projects' => PublicContent::snapshot()['projects']]);
    }

    public function project(string $slug): View
    {
        $project = PublicContent::project($slug) ?? abort(404);

        return view('pages.project', ['project' => $project]);
    }

    public function privacy(): View
    {
        return view('pages.privacy', ['settings' => PublicContent::settings()]);
    }

    public function terms(): View
    {
        return view('pages.terms', ['settings' => PublicContent::settings()]);
    }

    /** Preview sites (and live sites with indexing off) block all crawlers; otherwise everything except the admin is allowed. */
    public function robots(): Response
    {
        $body = Site::allowsIndexing()
            ? "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /orders/\nDisallow: /account\nDisallow: /login\nDisallow: /signup\nDisallow: /forgot-password\nDisallow: /reset-password/\nDisallow: /newsletter/\nDisallow: /stats/\nDisallow: /forum/new\n\nSitemap: ".Site::url('/sitemap.xml')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Public pages and items. Sample items are excluded (they are hidden on the live site anyway). */
    public function sitemap(): Response
    {
        $s = PublicContent::snapshot();
        $urls = collect(['/', '/services', '/packages', '/packages/build-your-own', '/products', '/projects', '/pv-calculator', '/about', '/contact', '/privacy', '/terms'])->map(fn ($p) => ['loc' => Site::url($p === '/' ? '/' : $p), 'lastmod' => null]);
        foreach ([['packages', '/packages/'], ['products', '/products/'], ['projects', '/projects/']] as [$key, $prefix]) {
            foreach ($s[$key]->where('is_sample', false) as $item) {
                $urls->push(['loc' => Site::url($prefix.$item->slug), 'lastmod' => $item->updated_at?->toAtomString()]);
            }
        }

        $urls->push(['loc' => Site::url('/forum'), 'lastmod' => null]);
        if (\App\Support\Forum::hasTables()) {
            foreach (\App\Models\ForumTopic::query()->where('status', 'published')->latest('last_post_at')->limit(1000)->get(['slug', 'last_post_at']) as $t) {
                $urls->push(['loc' => Site::url('/forum/'.$t->slug), 'lastmod' => $t->last_post_at?->toAtomString()]);
            }
        }

        return response()->view('sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
