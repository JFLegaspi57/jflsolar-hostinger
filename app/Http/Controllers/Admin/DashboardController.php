<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Product;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = collect([
            ['model' => Package::class, 'label' => 'Packages', 'href' => '/admin/packages'],
            ['model' => Product::class, 'label' => 'Products', 'href' => '/admin/products'],
            ['model' => Project::class, 'label' => 'Projects', 'href' => '/admin/projects'],
        ])->map(fn ($t) => $t + [
            'published' => $t['model']::query()->where('status', 'published')->count(),
            'drafts' => $t['model']::query()->where('status', 'draft')->count(),
            'samples' => $t['model']::query()->where('is_sample', true)->where('status', '!=', 'archived')->count(),
        ]);

        $pending = ContentBlock::query()->where('published', true)->where('claim_status', 'needs_confirmation')->count();
        $samplePrices = Package::query()->where('price_is_sample', true)->where('status', '!=', 'archived')->count();
        $settings = SiteSetting::query()->find(1);

        $todo = [];
        foreach ($stats as $s) {
            if ($s['samples']) {
                $todo[] = ['text' => "{$s['samples']} sample ".strtolower($s['label']).' to replace or archive', 'href' => $s['href']];
            }
        }
        if ($pending) {
            $todo[] = ['text' => "{$pending} content blocks awaiting owner confirmation", 'href' => '/admin/content'];
        }
        if ($samplePrices) {
            $todo[] = ['text' => "{$samplePrices} package(s) with sample prices", 'href' => '/admin/packages'];
        }
        if ($settings && ! $settings->facebook_enabled) {
            $todo[] = ['text' => 'Facebook link not verified/enabled', 'href' => '/admin/settings'];
        }
        if ($settings && preg_match('/to be confirmed/i', (string) $settings->general_warranty)) {
            $todo[] = ['text' => 'General warranty text still says “to be confirmed”', 'href' => '/admin/settings'];
        }
        if ($settings && str_ends_with((string) $settings->public_email, '@jflsolar.com')) {
            $todo[] = ['text' => 'Public email is still the placeholder '.$settings->public_email, 'href' => '/admin/settings'];
        }

        $notify = trim((string) config('site.inquiry.notify'));

        return view('admin.dashboard', [
            'stats' => $stats,
            'todo' => $todo,
            'newInquiries' => Inquiry::query()->where('status', 'new')->count(),
            'mail' => $notify === '' ? 'off' : (config('mail.default') === 'log' ? 'unconfigured' : 'on'),
            'notify' => $notify,
        ]);
    }
}
