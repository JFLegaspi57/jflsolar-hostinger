<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [\App\Http\Middleware\CountVisitors::class]);
        $middleware->validateCsrfTokens(except: ['pv', 'webhooks/paymongo']);   // time-on-page beacon; PayMongo webhook (signature-checked)
        // No proxies are trusted: the client address is the connection's address,
        // so visitors cannot spoof X-Forwarded-For to dodge rate limits. (Hostinger's
        // web server terminates HTTPS itself and sets HTTPS for PHP directly.)
        // Staff pages send signed-out visitors to the staff sign-in; member pages to /login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*') ? '/admin/login' : '/login');
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // An expired form (session timed out between loading and sending) goes back
        // to the form with everything the person typed, instead of a bare 419 page.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return redirect()->back(fallback: $request->is('admin/*') ? '/admin' : '/')
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'token']))
                ->with($request->is('contact') ? 'inquiry_message' : 'status_error', 'This page had been open for a long time, so the form expired. Your details are still here — please send it again.');
        });
    })->create();

// Hostinger Git deployment (docs/HOSTINGER.md, section 10): the repository is deployed
// into public_html, and every deploy replaces files there. Settings (.env) and
// everything written at run time (photos, sessions, cache, logs) therefore live in
// jflsolar-data/ next to public_html — untouched by deploys and unreachable from the web.
// A site first installed from a zip release keeps them in jflsolar-app/ instead, so
// connecting GitHub needs no file moves: that folder is used if jflsolar-data is absent.
// Other layouts (zip release, local development) are unchanged: in the zip layout the
// match is the application folder itself; locally neither folder exists.
foreach (['jflsolar-data', 'jflsolar-app'] as $folder) {
    $data = dirname($app->basePath()).DIRECTORY_SEPARATOR.$folder;
    if (is_file($data.DIRECTORY_SEPARATOR.'.env')) {
        $app->useEnvironmentPath($data);
        $app->useStoragePath($data.DIRECTORY_SEPARATOR.'storage');
        break;
    }
}

return $app;
