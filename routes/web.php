<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactController;
use App\Http\Middleware\EnsureStaff;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', [PageController::class, 'home']);
Route::get('/services', [PageController::class, 'services']);
Route::get('/about', [PageController::class, 'about']);
Route::get('/packages', [PageController::class, 'packages']);
Route::get('/packages/compare', [PageController::class, 'compare']);
Route::get('/packages/{slug}', [PageController::class, 'package'])->where('slug', '[a-z0-9-]+');
Route::get('/products', [PageController::class, 'products']);
Route::get('/products/{slug}', [PageController::class, 'product'])->where('slug', '[a-z0-9-]+');
Route::get('/projects', [PageController::class, 'projects']);
Route::get('/projects/{slug}', [PageController::class, 'project'])->where('slug', '[a-z0-9-]+');
Route::get('/pv-calculator', [PageController::class, 'calculator']);
// Old address: keep shared links working (query string carried over).
Route::get('/solar-calculator', fn (Request $r) => redirect()->to('/pv-calculator'.(($q = (string) $r->server('QUERY_STRING')) !== '' ? '?'.$q : ''), 301));
// Time-on-page reports from the page's own timing script (admin → Visitors). Sent with
// navigator.sendBeacon, so no CSRF token (see bootstrap/app.php); it can only fill in a
// duration for a page view the server itself recorded in the last few hours.
Route::post('/pv', function (Request $r) {
    $id = (string) $r->input('id', '');
    $s = (int) $r->input('s', 0);
    if (preg_match('/^[a-f0-9]{24}$/', $id)) {
        try {
            if ($r->input('start') === '1') {
                \App\Support\Visitors::start($id, $r->ip());   // page shown in a real browser: count it
            } elseif ($s > 0) {
                \App\Support\Visitors::recordDuration($id, $s);
            }
        } catch (\Throwable) {
        }
    }

    return response()->noContent();
})->middleware('throttle:beacon');
Route::get('/contact', [ContactController::class, 'show']);
Route::post('/contact', [ContactController::class, 'submit']);
// Online shop (off unless SHOP_ENABLED=true and PayMongo keys are set — see config/shop.php).
// Member (customer) accounts
Route::get('/signup', [Account\AuthController::class, 'showSignup']);
Route::post('/signup', [Account\AuthController::class, 'signup'])->middleware('throttle:account');
Route::get('/login', [Account\AuthController::class, 'showLogin']);
Route::post('/login', [Account\AuthController::class, 'login'])->middleware('throttle:account');
Route::post('/logout', [Account\AuthController::class, 'logout']);
Route::get('/forgot-password', [Account\AuthController::class, 'showForgot']);
Route::post('/forgot-password', [Account\AuthController::class, 'forgot'])->middleware('throttle:account');
Route::get('/reset-password/{token}', [Account\AuthController::class, 'showReset'])->where('token', '[A-Za-z0-9]{20,100}');
Route::post('/reset-password', [Account\AuthController::class, 'reset'])->middleware('throttle:account');
Route::get('/account', [Account\AccountController::class, 'show']);
Route::post('/account', [Account\AccountController::class, 'update']);
Route::post('/account/password', [Account\AccountController::class, 'password'])->middleware('throttle:account');
Route::post('/account/delete', [Account\AccountController::class, 'destroy'])->middleware('throttle:account');
Route::post('/account/profile', [Account\AccountController::class, 'profile'])->middleware('throttle:account');
Route::get('/account/photo/{member}/{file}', [Account\AccountController::class, 'photo'])->whereNumber('member')->where('file', '[A-Za-z0-9]{24}\.webp');

// Forum: everyone can read; registered users post (first post reviewed by staff)
Route::get('/forum', [\App\Http\Controllers\ForumController::class, 'index']);
Route::get('/forum/new', [\App\Http\Controllers\ForumController::class, 'create']);
Route::post('/forum', [\App\Http\Controllers\ForumController::class, 'store'])->middleware('throttle:forum');
Route::get('/forum/{slug}', [\App\Http\Controllers\ForumController::class, 'show'])->where('slug', '[a-z0-9-]{1,120}');
Route::post('/forum/{slug}/reply', [\App\Http\Controllers\ForumController::class, 'reply'])->where('slug', '[a-z0-9-]{1,120}')->middleware('throttle:forum');

// Newsletter (promos and news)
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'showUnsubscribe'])->where('token', '[A-Za-z0-9]{40}');
Route::post('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:account');

Route::get('/cart', [Shop\CartController::class, 'show']);
Route::post('/cart', [Shop\CartController::class, 'update']);
Route::post('/cart/add', [Shop\CartController::class, 'add'])->middleware('throttle:cart');
Route::post('/packages/{slug}/basket', [Shop\PackageBasketController::class, 'add'])->where('slug', '[a-z0-9-]+')->middleware('throttle:cart');
Route::get('/checkout', [Shop\CheckoutController::class, 'show']);
Route::post('/checkout', [Shop\CheckoutController::class, 'submit'])->middleware('throttle:checkout');
Route::get('/checkout/done', [Shop\CheckoutController::class, 'done']);
Route::get('/checkout/preview', [Shop\CheckoutController::class, 'preview']);
Route::get('/checkout/preview/print', [Shop\CheckoutController::class, 'previewPrint']);
Route::get('/orders/{reference}/print', [Shop\CheckoutController::class, 'print'])->where('reference', 'JFL-[0-9]{6}-[A-Z0-9]{4}')->middleware('throttle:orders');
Route::get('/orders/{reference}', [Shop\CheckoutController::class, 'status'])->where('reference', 'JFL-[0-9]{6}-[A-Z0-9]{4}')->middleware('throttle:orders');
// PayMongo → site, verified by signature instead of a CSRF token (bootstrap/app.php).
Route::post('/webhooks/paymongo', Shop\PayMongoWebhookController::class)->middleware('throttle:webhook');
Route::get('/privacy', [PageController::class, 'privacy']);
Route::get('/terms', [PageController::class, 'terms']);
Route::get('/robots.txt', [PageController::class, 'robots']);
Route::get('/stats/visitors', fn () => response()->json(\App\Support\Visitors::publicStats() ?? (object) [])->header('Cache-Control', 'no-store'))->middleware('throttle:stats');
Route::get('/sitemap.xml', [PageController::class, 'sitemap']);
Route::get('/media/{path}', [MediaController::class, 'public'])->where('path', '.*');

// Admin
Route::prefix('admin')->group(function () {
    Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login']);
    Route::get('recover', [Admin\AuthController::class, 'showRecover']);
    Route::post('recover', [Admin\AuthController::class, 'recover']);

    Route::middleware(['auth', EnsureStaff::class])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout']);
        Route::get('password', [Admin\AuthController::class, 'showPassword']);
        Route::post('password', [Admin\AuthController::class, 'updatePassword']);

        Route::get('/', [Admin\DashboardController::class, 'index']);
        Route::get('visitors', [Admin\VisitorsController::class, 'index']);
        Route::get('media/{path}', [MediaController::class, 'admin'])->where('path', '(packages|products|projects|blocks)/.*');

        $uuid = '[0-9a-f-]{36}';
        foreach (['packages' => Admin\PackageController::class, 'products' => Admin\ProductController::class, 'projects' => Admin\ProjectController::class, 'categories' => Admin\CategoryController::class] as $entity => $controller) {
            Route::get($entity, [$controller, 'index']);
            Route::get("{$entity}/new", [$controller, 'create']);
            Route::post($entity, [$controller, 'store']);
            Route::get("{$entity}/{id}", [$controller, 'edit'])->where('id', $uuid);
            Route::post("{$entity}/{id}", [$controller, 'update'])->where('id', $uuid);
        }
        $records = ['entity' => 'packages|products|projects|categories', 'id' => $uuid];
        Route::post('{entity}/{id}/status', [Admin\RecordController::class, 'status'])->where($records);
        Route::post('{entity}/{id}/featured', [Admin\RecordController::class, 'featured'])->where($records);
        Route::post('{entity}/{id}/move', [Admin\RecordController::class, 'move'])->where($records);
        Route::post('{entity}/{id}/delete', [Admin\RecordController::class, 'destroy'])->where($records);
        Route::get('preview/{entity}/{id}', [Admin\PreviewController::class, 'show'])->where(['entity' => 'packages|products|projects', 'id' => $uuid]);

        Route::post('media/upload/{type}/{owner}', [Admin\MediaAdminController::class, 'upload'])->where('type', 'package|product|project|block');
        Route::post('media/{asset}/text', [Admin\MediaAdminController::class, 'text'])->where('asset', $uuid);
        Route::post('media/{asset}/op', [Admin\MediaAdminController::class, 'op'])->where('asset', $uuid);
        Route::post('media/{asset}/replace', [Admin\MediaAdminController::class, 'replace'])->where('asset', $uuid);
        Route::post('products/{id}/datasheet', [Admin\MediaAdminController::class, 'datasheet'])->where('id', $uuid);
        Route::post('products/{id}/datasheet/delete', [Admin\MediaAdminController::class, 'deleteDatasheet'])->where('id', $uuid);
        Route::post('products/{id}/manual', [Admin\MediaAdminController::class, 'manual'])->where('id', $uuid);
        Route::post('products/{id}/manual/delete', [Admin\MediaAdminController::class, 'deleteManual'])->where('id', $uuid);
        Route::post('products/{id}/manual/link', [Admin\MediaAdminController::class, 'manualLink'])->where('id', $uuid);

        Route::get('content', [Admin\ContentController::class, 'index']);
        Route::get('content/new', [Admin\ContentController::class, 'create']);
        Route::post('content', [Admin\ContentController::class, 'store']);
        Route::get('content/{key}', [Admin\ContentController::class, 'edit'])->where('key', '[a-z0-9._-]+');
        Route::post('content/{key}', [Admin\ContentController::class, 'update'])->where('key', '[a-z0-9._-]+');
        Route::post('content/{key}/delete', [Admin\ContentController::class, 'destroy'])->where('key', '[a-z0-9._-]+');

        Route::get('text', fn () => redirect('/admin/text/home'));
        Route::get('text/{page}', [Admin\PageTextController::class, 'edit'])->where('page', 'home|services|about');
        Route::post('text/{page}', [Admin\PageTextController::class, 'update'])->where('page', 'home|services|about');

        Route::get('settings', [Admin\SettingsController::class, 'edit']);
        Route::post('settings', [Admin\SettingsController::class, 'update']);

        Route::get('forum', [Admin\ForumController::class, 'index']);
        Route::post('forum/posts/{post}', [Admin\ForumController::class, 'post'])->whereNumber('post');
        Route::post('forum/topics/{topic}', [Admin\ForumController::class, 'topic'])->whereNumber('topic');
        Route::get('members', [Admin\MemberController::class, 'index']);
        Route::get('members/export', [Admin\MemberController::class, 'exportMembers']);
        Route::get('members/{member}', [Admin\MemberController::class, 'show'])->whereNumber('member');
        Route::post('members/{member}', [Admin\MemberController::class, 'update'])->whereNumber('member');
        Route::get('subscribers', [Admin\MemberController::class, 'subscribers']);
        Route::get('subscribers/export', [Admin\MemberController::class, 'export']);
        Route::get('orders', [Admin\OrderController::class, 'index']);
        Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->whereNumber('order');
        Route::post('orders/{order}', [Admin\OrderController::class, 'update'])->whereNumber('order');

        Route::get('inquiries', [Admin\InquiryController::class, 'index']);
        Route::get('inquiries/{inquiry}', [Admin\InquiryController::class, 'show'])->whereNumber('inquiry');
        Route::post('inquiries/{inquiry}', [Admin\InquiryController::class, 'update'])->whereNumber('inquiry');
        Route::post('inquiries/{inquiry}/delete', [Admin\InquiryController::class, 'destroy'])->whereNumber('inquiry');

        Route::middleware(EnsureStaff::class.':owner')->group(function () {
            Route::get('staff', [Admin\StaffController::class, 'index']);
            Route::post('staff', [Admin\StaffController::class, 'store']);
            Route::post('staff/{user}/role', [Admin\StaffController::class, 'role'])->whereNumber('user');
            Route::post('staff/{user}/active', [Admin\StaffController::class, 'active'])->whereNumber('user');
            Route::post('staff/{user}/reset', [Admin\StaffController::class, 'reset'])->whereNumber('user');
            Route::post('staff/{user}/delete', [Admin\StaffController::class, 'destroy'])->whereNumber('user');

            Route::get('facebook-reviews', [Admin\FacebookReviewsController::class, 'show']);
            Route::post('facebook-reviews/connect', [Admin\FacebookReviewsController::class, 'connect']);
            Route::post('facebook-reviews/sync', [Admin\FacebookReviewsController::class, 'sync']);
            Route::post('facebook-reviews/disconnect', [Admin\FacebookReviewsController::class, 'disconnect']);
        });
    });
});
