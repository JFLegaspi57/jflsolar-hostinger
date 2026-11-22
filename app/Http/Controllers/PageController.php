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
        return $this->projectView($slug, false);
    }

    /** Filipino version of a project page: same photos and videos as the English one. */
    public function projectTagalog(string $slug): View
    {
        return $this->projectView($slug, true);
    }

    private function projectView(string $slug, bool $tagalog): View
    {
        \App\Support\ProjectTl::ensureColumns();
        $project = PublicContent::project($slug) ?? abort(404);
        $tl = \App\Support\ProjectTl::tagalog($project);
        abort_if($tagalog && ! $tl, 404);

        return view('pages.project', ['project' => $project, 'tagalog' => $tagalog, 'tl' => $tl]);
    }

    public function faq(): View
    {
        $settings = PublicContent::settings();

        return view('pages.faq', ['settings' => $settings, 'items' => \App\Support\Faq::items($settings), 'tl' => false]);
    }

    /** Tagalog version of the FAQ. */
    public function faqTagalog(): View
    {
        $settings = PublicContent::settings();

        return view('pages.faq', ['settings' => $settings, 'items' => \App\Support\FaqTagalog::items($settings), 'tl' => true]);
    }

    /** Estimated delivery fees from the warehouse to towns within about 100 km. */
    public function deliveryRates(): View
    {
        return view('pages.delivery-rates', ['settings' => PublicContent::settings(), 'areas' => \App\Support\DeliveryRates::areas(), 'range' => \App\Support\DeliveryRates::range()]);
    }

    /** How customers can pay (under About JFL Solar in the menu). */
    public function paymentMethods(): View
    {
        return view('pages.payment-methods', ['settings' => PublicContent::settings()]);
    }

    public function netMetering(): View
    {
        return view('pages.net-metering', ['settings' => PublicContent::settings()]);
    }

    /** Tagalog version of the net metering guide. */
    public function netMeteringTagalog(): View
    {
        return view('pages.net-metering-tl', ['settings' => PublicContent::settings()]);
    }

    /** JF Legaspi's blog. */
    public function blog(): View
    {
        $posts = \App\Support\Blog::published();
        // Banner: a random photo from all published posts (a new one on each visit); small photos would look
        // blurry, and step-by-step posters are left out (Blog::bannerPhoto).
        $photos = $posts->flatMap(fn ($p) => $p->photos)->filter(fn ($m) => \App\Support\Blog::bannerPhoto($m));

        $banner = $photos->isNotEmpty() ? $photos->random() : null;

        // The banner photo links to the post it comes from.
        return view('pages.blog', ['posts' => $posts, 'banner' => $banner, 'bannerPost' => $banner ? $posts->firstWhere('id', $banner->blog_post_id) : null]);
    }

    public function blogPost(string $slug): View
    {
        return $this->blogPostView($slug, false);
    }

    /** Filipino version of a post: same photos, reactions and comments as the English one. */
    public function blogPostTagalog(string $slug): View
    {
        return $this->blogPostView($slug, true);
    }

    private function blogPostView(string $slug, bool $tagalog): View
    {
        // Signed-in staff can also open a draft post at its own address (a staff-only preview).
        $post = \App\Support\Blog::find($slug)
            ?? (auth('web')->check() ? \App\Support\Blog::findUnpublished($slug) : null)
            ?? abort(404);
        $tl = \App\Support\Blog::tagalog($post);
        abort_if($tagalog && ! $tl, 404);

        $member = \App\Support\Members::current();
        // Members (and signed-in staff) read the whole post; visitors see the opening and an invitation to sign up.
        $fullAccess = $member !== null || auth('web')->check();

        return view('pages.blog-post', [
            'post' => $post, 'settings' => PublicContent::settings(), 'member' => $member, 'fullAccess' => $fullAccess,
            'tagalog' => $tagalog, 'tl' => $tl,
            'reactions' => \App\Support\BlogComments::counts($post), 'myReaction' => \App\Support\BlogComments::memberReaction($post, $member),
            'comments' => \App\Support\BlogComments::visible($post, $member),
        ]);
    }

    /** Technical guide: how a hybrid solar PV system works. */
    public function hybridGuide(): View
    {
        return view('pages.hybrid-pv-guide', ['settings' => PublicContent::settings()]);
    }

    /** Tagalog version of the hybrid guide. */
    public function hybridGuideTagalog(): View
    {
        return view('pages.hybrid-pv-guide-tl', ['settings' => PublicContent::settings()]);
    }

    /** Technical guide: how a grid-tied solar PV system works. */
    public function gridTiedGuide(): View
    {
        return view('pages.grid-tied-pv-guide', ['settings' => PublicContent::settings()]);
    }

    /** Tagalog version of the grid-tied guide. */
    public function gridTiedGuideTagalog(): View
    {
        return view('pages.grid-tied-pv-guide-tl', ['settings' => PublicContent::settings()]);
    }

    /** Brand articles (/brands/{slug}); the brand logos on /products link here. */
    /** Our Brands: every brand we carry, grouped by product type, linking to its article and products. */
    public function brands(): View
    {
        $products = PublicContent::snapshot()['products'];
        $available = \App\Support\BrandArticles::available();
        $groups = [];
        foreach (\App\Support\BrandArticles::GROUPS as $key => [$heading, $line]) {
            $items = [];
            $entries = [];
            foreach (\App\Support\BrandArticles::OVERVIEW as $slug => [$group, $summary]) {
                $entries[] = [$slug, $group, $summary, null, null, null];
            }
            foreach ([...$entries, ...\App\Support\BrandArticles::OVERVIEW_EXTRA] as [$key2, $group, $summary, $category, $section, $tier]) {
                if ($group !== $key) {
                    continue;
                }
                $a = $available[$key2] ?? null;
                if (! $a && isset(\App\Support\BrandArticles::ARTICLES[$key2])) {
                    continue;   // article not written yet
                }
                $filter = $a ? $a['filter'] : $key2;
                $list = $products->filter(fn ($p) => strcasecmp((string) $p->brand, $filter) === 0);
                if ($category) {
                    $list = $list->filter(fn ($p) => $p->category?->slug === $category);
                }
                if (! $a && $list->isEmpty()) {
                    continue;   // brand without an article is shown only while it has products
                }
                $items[] = [
                    'brand' => $a ? $a['brand'] : $key2,
                    'slug' => $a ? $key2 : null,
                    'summary' => $summary,
                    // Only Tier 1 is shown (owner's choice); other tier labels stay hidden.
                    'tier' => $tier ?? ($a && ! $category && (\App\Support\BrandArticles::TIERS[$key2][1] ?? false) ? \App\Support\BrandArticles::TIERS[$key2][0] : null),
                    'count' => $list->count(),
                    'article' => $a ? '/brands/'.$key2.($section ? '#'.$section : '') : null,
                    'productsHref' => '/products?'.http_build_query(array_filter(['brand' => $filter, 'category' => $category])).'#results',
                ];
            }
            if ($items) {
                $groups[] = ['key' => $key, 'heading' => $heading, 'line' => $line, 'items' => $items];
            }
        }

        return view('pages.brands-index', ['settings' => PublicContent::settings(), 'groups' => $groups]);
    }

    public function brand(string $slug): View
    {
        $a = \App\Support\BrandArticles::available()[$slug] ?? abort(404);

        return view('pages.brands.'.$slug, [
            'settings' => PublicContent::settings(),
            'products' => \App\Support\BrandArticles::products(PublicContent::snapshot()['products'], $slug),
            'article' => ['slug' => $slug] + $a,
        ]);
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
        $urls = collect(['/', '/services', '/packages', '/packages/build-your-own', '/products', '/projects', '/pv-calculator', '/faq', '/faq/tagalog', '/delivery-rates', '/payment-methods', '/brands', '/net-metering', '/net-metering/tagalog', '/hybrid-pv-system-guide', '/hybrid-pv-system-guide/tagalog', '/grid-tied-pv-system-guide', '/grid-tied-pv-system-guide/tagalog', ...array_map(fn ($s) => '/brands/'.$s, array_keys(\App\Support\BrandArticles::available())), '/about', '/contact', '/privacy', '/terms'])->map(fn ($p) => ['loc' => Site::url($p === '/' ? '/' : $p), 'lastmod' => null]);
        foreach ([['packages', '/packages/'], ['products', '/products/'], ['projects', '/projects/']] as [$key, $prefix]) {
            foreach ($s[$key]->where('is_sample', false) as $item) {
                $urls->push(['loc' => Site::url($prefix.$item->slug), 'lastmod' => $item->updated_at?->toAtomString()]);
            }
        }

        $urls->push(['loc' => Site::url('/account/quotation'), 'lastmod' => null]);
        $urls->push(['loc' => Site::url('/blog'), 'lastmod' => null]);
        foreach (\App\Support\Blog::published()->where('is_sample', false) as $post) {
            $urls->push(['loc' => Site::url('/blog/'.$post->slug), 'lastmod' => $post->updated_at?->toAtomString()]);
            if (\App\Support\Blog::tagalog($post)) {
                $urls->push(['loc' => Site::url('/blog/'.$post->slug.'/tagalog'), 'lastmod' => $post->updated_at?->toAtomString()]);
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
