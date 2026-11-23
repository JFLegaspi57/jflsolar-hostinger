<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Seminars and workshops JF speaks at, each with its own page under /services/training-seminars/{slug}.
 * Upcoming ones (until their last day, Manila time) are also announced on the Training & Seminars page.
 * 'full' => true marks a seminar as fully booked (badge shown, no Reserve a Slot button).
 */
class Seminars
{
    public const BASE = '/services/training-seminars';

    public const ALL = [
        'solar-pv-installation-seminar-surigao-2026' => [
            'title' => 'Solar Photovoltaic Installation',
            'kind' => '2-Day Seminar & Workshop',
            'tagline' => 'From Sunlight to Solutions: Advancing Solar PV Installation Skills and Technology',
            'start' => '2026-11-28',
            'end' => '2026-11-29',
            'when' => 'November 28–29, 2026',
            'where' => 'P-4, Brgy. Bonifacio, Surigao City, Surigao del Norte',
            'who' => 'Open to all DIYers and electricians',
            'partner' => 'Early Sky Builders Inc.',
            'full' => true, // Fully booked: no more reservations.
            'poster' => 'seminar-surigao-2026-poster',
            'speakers' => [
                ['Guest Speaker', 'JF Legaspi', 'JFL Solar CEO & Founder of Lithium Power Philippines'],
                ['Lecturer', 'Engr. Joselito S. Baldapan, PEE', 'Founder of Early Sky Builders Inc. and lecturer at the Early Sky Training and Development Center'],
            ],
            'topics' => [
                ['Introduction to Solar Energy', 'Basics, benefits and current trends'],
                ['System Components and Design', 'Solar panels, inverters, batteries and more'],
                ['Actual Demonstration', 'Hands-on installation and system setup, from small to bigger setups'],
                ['Installation Guidelines and Safety', 'Step-by-step process and safety considerations'],
                ['Q&A and Knowledge Sharing', 'Insights from industry practitioners'],
            ],
        ],
    ];

    public static function find(string $slug): ?array
    {
        return isset(self::ALL[$slug]) ? self::ALL[$slug] + ['slug' => $slug, 'url' => self::BASE.'/'.$slug] : null;
    }

    public static function isOver(array $s): bool
    {
        return CarbonImmutable::now('Asia/Manila')->toDateString() > $s['end'];
    }

    /** Seminars that have not ended yet, soonest first. */
    public static function upcoming(): array
    {
        return collect(array_keys(self::ALL))->map(fn ($k) => self::find($k))
            ->reject(fn ($s) => self::isOver($s))->sortBy('start')->values()->all();
    }
}
