<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ContactController;
use App\Http\Middleware\EnsureStaff;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
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
Route::get('/contact', [ContactController::class, 'show']);
Route::post('/contact', [ContactController::class, 'submit']);
Route::get('/privacy', [PageController::class, 'privacy']);
Route::get('/terms', [PageController::class, 'terms']);
Route::get('/robots.txt', [PageController::class, 'robots']);
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
        });
    });
});
