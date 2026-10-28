<?php

namespace App\Http\Controllers;

use App\Mail\NewInquiry;
use App\Models\Inquiry;
use App\Support\Contact;
use App\Support\Inquiries;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $s = PublicContent::snapshot();
        $pick = fn (string $k) => mb_substr((string) (is_array($request->query($k)) ? '' : $request->query($k, '')), 0, 80);

        // Values come back from a failed submission (old input) or start from the query string.
        $values = array_fill_keys(['name', 'email', 'phone', 'inquiry_type', 'message', 'location', 'bill_range', 'item_type', 'item_slug', 'item_name'], '');
        $pkg = $s['packages']->firstWhere('slug', $pick('package'));
        $product = $pkg ? null : $s['products']->firstWhere('slug', $pick('product'));
        if ($pkg) {
            $values = array_merge($values, ['item_type' => 'package', 'item_slug' => $pkg->slug, 'item_name' => $pkg->name, 'inquiry_type' => 'package']);
        } elseif ($product) {
            $values = array_merge($values, ['item_type' => 'product', 'item_slug' => $product->slug, 'item_name' => $product->name, 'inquiry_type' => 'equipment']);
        }
        if ($request->session()->hasOldInput()) {
            $values = array_merge($values, array_intersect_key((array) $request->old(), $values));
        }

        return view('pages.contact', [
            'settings' => $s['settings'],
            'values' => $values,
            'token' => $request->session()->pull('inquiry_token') ?? Inquiries::issueToken(),
            'stale' => ($pick('package') !== '' && ! $pkg) || ($pick('product') !== '' && ! $product),
            'quickContacts' => collect(Contact::channels($s['settings']))->whereIn('key', ['phone', 'whatsapp', 'viber'])->values(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $values = Inquiries::read($request->all());
        $back = fn (string $message, array $errors = [], ?string $token = null) => redirect()->to('/contact#quote')
            ->withInput($values)
            ->withErrors($errors)
            ->with('inquiry_message', $message)
            ->with('inquiry_token', $token ?? Inquiries::issueToken());

        // Honeypot: real visitors never see or fill this field. Bots get a neutral response; nothing is saved.
        if (trim((string) $request->input('website', '')) !== '') {
            return redirect()->to('/contact#quote')->with('inquiry_sent', true);
        }

        $token = Inquiries::checkToken($request->input('token'));
        if ($token === 'too_fast') {
            return $back('That was quick! Please check your details, then select Send inquiry again.', [], $request->input('token'));
        }
        if ($token !== 'ok') {
            // Back-dated so the immediate retry is not rejected as "too fast".
            return $back('This form had expired. Your details are still here — please select Send inquiry again.', [], Inquiries::issueToken(time() - Inquiries::MIN_FILL_SECONDS));
        }

        $errors = Inquiries::validate($values);

        // Re-resolve the selected item against what is publicly visible right now.
        $itemLabel = null;
        if ($values['item_slug'] !== '' && ! isset($errors['item'])) {
            $found = $values['item_type'] === 'package' ? PublicContent::package($values['item_slug']) : PublicContent::product($values['item_slug']);
            if (! $found) {
                $values['item_type'] = $values['item_slug'] = $values['item_name'] = '';
                $errors['item'] = 'The item you selected is no longer available, so we removed it. Please check your message and send again.';
            } else {
                $values['item_name'] = $found->name;
                $itemLabel = ($values['item_type'] === 'package' ? 'Package' : 'Product').': '.$found->name.($found->is_sample ? ' (sample listing)' : '');
            }
        }

        if ($errors) {
            return $back('Please correct the highlighted fields.', $errors, $request->input('token'));
        }

        // Rate limits: per client (salted fingerprint, expires on its own) and a site-wide daily cap.
        $key = Inquiries::clientKey((string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, Inquiries::CLIENT_LIMIT)) {
            return $back("You've sent several inquiries in a short time. Please wait a few minutes, or contact us directly by phone or message.", [], $request->input('token'));
        }
        if (Inquiry::query()->where('created_at', '>', now()->subDay())->count() >= config('site.inquiry.daily_cap')) {
            return $back('Our inquiry form is busy right now. Please contact us directly by phone, WhatsApp or Viber.', [], $request->input('token'));
        }
        RateLimiter::hit($key, Inquiries::CLIENT_WINDOW_SECONDS);

        $inquiry = Inquiry::query()->create([
            'name' => $values['name'],
            'phone' => $values['phone'] ?: null,
            'email' => $values['email'] ?: null,
            'inquiry_type' => $values['inquiry_type'],
            'location' => $values['location'] ?: null,
            'bill_range' => $values['bill_range'] ?: null,
            'item_type' => $values['item_type'] ?: null,
            'item_slug' => $values['item_slug'] ?: null,
            'item_label' => $itemLabel,
            'message' => $values['message'],
        ]);

        // Email alert to the business inbox (optional). A delivery failure never loses the inquiry.
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('site.inquiry.notify')))));
        if ($to && config('mail.default') !== 'log') {
            try {
                Mail::to($to)->send(new NewInquiry($inquiry));
                $inquiry->forceFill(['emailed_at' => now()])->save();
            } catch (\Throwable $e) {
                Log::error('[inquiry] email alert failed', ['inquiry' => $inquiry->id, 'error' => $e::class]);
            }
        }

        return redirect()->to('/contact#quote')->with('inquiry_sent', true);
    }
}
