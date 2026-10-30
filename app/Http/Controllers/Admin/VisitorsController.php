<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Visitors;
use Illuminate\View\View;

/** Daily, weekly, monthly and yearly unique / new / returning visitor counts. */
class VisitorsController extends Controller
{
    public function index(): View
    {
        $periods = array_map(fn ($p) => $p + Visitors::between($p['from'], $p['to']), Visitors::periods());

        return view('admin.visitors', [
            'periods' => $periods,
            'daily' => Visitors::daily(30),
            'allTime' => Visitors::allTime(),
        ]);
    }
}
