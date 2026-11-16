<?php

namespace App\Http\Middleware;

use App\Support\Lang;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Visitors who chose Filipino are sent to the Filipino version of a page when there is one. */
class PreferredLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        // Course lessons always open in English; the Tagalog version is one click away on the lesson.
        if ($request->isMethod('GET') && ! $request->ajax() && Lang::current($request) === 'fil' && ! $request->is('courses/*')
            && ($to = Lang::tagalogPath('/'.$request->path())) !== null) {
            return redirect($to.($request->getQueryString() ? '?'.$request->getQueryString() : ''));
        }

        return $next($request);
    }
}
