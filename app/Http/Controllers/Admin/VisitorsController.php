<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GeoIp;
use App\Support\Visitors;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Daily, weekly, monthly and yearly unique / new / returning visitors, and where they come from. */
class VisitorsController extends Controller
{
    public function index(Request $request): View
    {
        $periods = array_map(fn ($p) => $p + Visitors::between($p['from'], $p['to']), Visitors::periods());
        $placesPeriod = collect($periods)->firstWhere('key', $request->query('period')) ?? collect($periods)->firstWhere('key', 'month');
        GeoIp::refreshIfDue();   // make sure the location database is (being) downloaded

        return view('admin.visitors', [
            'periods' => $periods,
            'daily' => Visitors::daily(30),
            'allTime' => Visitors::allTime(),
            'placesPeriod' => $placesPeriod,
            'places' => Visitors::places($placesPeriod['from'], $placesPeriod['to']),
            'activity' => Visitors::activity($placesPeriod['from'], $placesPeriod['to']),
            'geo' => ['ready' => GeoIp::ready()] + GeoIp::status(),
        ]);
    }
}
