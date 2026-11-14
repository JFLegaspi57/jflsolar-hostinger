<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\NewInquiry;
use App\Models\Inquiry;
use App\Models\MemberQuote;
use App\Support\Input;
use App\Support\Members;
use App\Support\PublicContent;
use App\Support\Quotations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** "Make a Quotation": members build, keep, print and send quote requests (see App\Support\Quotations). */
class QuotationController extends Controller
{
    /** The member's quotations; visitors see what it is and how to sign up. */
    public function index(): View
    {
        $member = Members::current();
        $quotes = collect();
        if ($member) {
            Quotations::ensureTables();
            $quotes = MemberQuote::query()->where('member_id', $member->id)->latest('updated_at')->get();
        }

        return view('pages.quotation.index', ['member' => $member, 'quotes' => $quotes]);
    }

    /** Start a new, empty quotation (optionally with one product already on it). */
    public function store(Request $request): RedirectResponse
    {
        $member = $this->member();
        Quotations::ensureTables();
        if (MemberQuote::query()->where('member_id', $member->id)->count() >= Quotations::MAX_QUOTES) {
            return redirect('/quotation')->with('quote_error', 'You have '.Quotations::MAX_QUOTES.' quotations, the most you can keep. Please delete one you no longer need first.');
        }
        $items = [];
        if ($p = Quotations::available()->get(Input::str($request, 'product_id'))) {
            $items[] = ['kind' => 'product', 'product_id' => $p->id, 'name' => $p->name, 'qty' => 1];
        }
        $n = MemberQuote::query()->where('member_id', $member->id)->count() + 1;
        $q = MemberQuote::query()->create([
            'member_id' => $member->id, 'title' => 'My quotation '.$n, 'phone' => $member->phone, 'items' => $items,
        ]);

        return redirect('/quotation/'.$q->id)->with('quote_status', $items ? 'New quotation started with '.$items[0]['name'].'.' : 'New quotation started. Add products and your own items below.');
    }

    public function edit(int $id): View
    {
        $q = $this->find($id);
        $catalogue = Quotations::available();

        return view('pages.quotation.edit', [
            'q' => $q, 'sum' => Quotations::lines($q), 'member' => Members::current(),
            'groups' => $catalogue->groupBy(fn ($p) => $p->category?->name ?? 'Other')->sortKeys(),
        ]);
    }

    /** Save the form; then do the button's action: add a product or own item, remove a line, or send. */
    public function update(Request $request, int $id): RedirectResponse
    {
        $q = $this->find($id);
        $back = '/quotation/'.$q->id;
        $wasSent = $q->sent_at !== null;
        $before = json_encode([$q->title, $q->location, $q->notes, $q->items]);

        $title = Quotations::clean($request->input('title'), Quotations::LIMITS['title']);
        $q->title = $title !== '' ? $title : $q->title;
        $q->location = Quotations::clean($request->input('location'), Quotations::LIMITS['location']) ?: null;
        $q->phone = Quotations::clean($request->input('phone'), Quotations::LIMITS['phone']) ?: null;
        $q->notes = Quotations::clean($request->input('notes'), Quotations::LIMITS['notes'], true) ?: null;

        // Existing lines: quantity, and for own items the name, unit and note.
        $posted = is_array($request->input('items')) ? $request->input('items') : [];
        $items = [];
        foreach (array_values((array) $q->items) as $i => $item) {
            $row = is_array($posted[$i] ?? null) ? $posted[$i] : [];
            $item['qty'] = Quotations::qty($row['qty'] ?? ($item['qty'] ?? 1));
            $item['note'] = array_key_exists('note', $row) ? Quotations::clean($row['note'], Quotations::LIMITS['note']) : (string) ($item['note'] ?? '');
            if (($item['kind'] ?? '') === 'custom') {
                $name = Quotations::clean($row['name'] ?? ($item['name'] ?? ''), Quotations::LIMITS['name']);
                $item['name'] = $name !== '' ? $name : (string) ($item['name'] ?? 'Item');
                $item['unit'] = array_key_exists('unit', $row) ? Quotations::clean($row['unit'], Quotations::LIMITS['unit']) : (string) ($item['unit'] ?? '');
            }
            $items[] = $item;
        }

        $intent = Input::str($request, 'intent', 'save');
        $status = 'Saved.';
        if (preg_match('/^remove:(\d+)$/', $intent, $m) && isset($items[(int) $m[1]])) {
            $status = 'Removed “'.$items[(int) $m[1]]['name'].'”.';
            array_splice($items, (int) $m[1], 1);
        } elseif (preg_match('/^(up|down):(\d+)$/', $intent, $m) && isset($items[(int) $m[2]])) {
            $from = (int) $m[2];
            $to = $m[1] === 'up' ? $from - 1 : $from + 1;
            if (isset($items[$to])) {
                [$items[$from], $items[$to]] = [$items[$to], $items[$from]];
            }
            $status = 'Moved.';
        } elseif ($intent === 'add_product' || $intent === 'add_custom') {
            if (count($items) >= Quotations::MAX_LINES) {
                return $this->save($q, $items, $wasSent, $before)->with('quote_error', 'A quotation can have up to '.Quotations::MAX_LINES.' lines. Start a new quotation for more.');
            }
            if ($intent === 'add_product') {
                $id = Input::str($request, 'add_product_id');
                $p = Quotations::available()->get($id);
                if (! $p) {
                    $msg = Quotations::catalogue()->has($id) ? 'That product is currently not available to order. Please choose another, or add it as your own item.' : 'Choose a product from the list first.';

                    return $this->save($q, $items, $wasSent, $before)->with('quote_error', $msg)->with('quote_focus', 'add-product');
                }
                $qty = Quotations::qty($request->input('add_qty', 1));
                $existing = collect($items)->search(fn ($it) => ($it['kind'] ?? '') === 'product' && ($it['product_id'] ?? '') === $p->id);
                if ($existing !== false) {
                    $items[$existing]['qty'] = Quotations::qty($items[$existing]['qty'] + $qty);
                    $status = 'Added '.$qty.' more of “'.$p->name.'”.';
                } else {
                    $items[] = ['kind' => 'product', 'product_id' => $p->id, 'name' => $p->name, 'qty' => $qty, 'note' => ''];
                    $status = 'Added “'.$p->name.'”.';
                }
            } else {
                $name = Quotations::clean($request->input('custom_name'), Quotations::LIMITS['name']);
                if (mb_strlen($name) < 2) {
                    return $this->save($q, $items, $wasSent, $before)->with('quote_error', 'Type a name for your item, e.g. “Installation labour” or “Ground-mount frame”.')->with('quote_focus', 'custom-name');
                }
                $items[] = ['kind' => 'custom', 'name' => $name, 'qty' => Quotations::qty($request->input('custom_qty', 1)),
                    'unit' => Quotations::clean($request->input('custom_unit'), Quotations::LIMITS['unit']), 'note' => ''];
                $status = 'Added “'.$name.'”.';
            }
        }

        $response = $this->save($q, $items, $wasSent, $before);

        return match ($intent) {
            'send' => $this->send($q),
            'print' => redirect('/quotation/'.$q->id.'/print'),
            default => $response->with('quote_status', $status),
        };
    }

    public function duplicate(int $id): RedirectResponse
    {
        $q = $this->find($id);
        if (MemberQuote::query()->where('member_id', $q->member_id)->count() >= Quotations::MAX_QUOTES) {
            return redirect('/quotation')->with('quote_error', 'You have '.Quotations::MAX_QUOTES.' quotations, the most you can keep. Please delete one you no longer need first.');
        }
        $copy = MemberQuote::query()->create([
            'member_id' => $q->member_id, 'title' => mb_substr('Copy of '.$q->title, 0, Quotations::LIMITS['title']),
            'location' => $q->location, 'phone' => $q->phone, 'notes' => $q->notes, 'items' => $q->items,
        ]);

        return redirect('/quotation/'.$copy->id)->with('quote_status', 'Copied. This is your new quotation; the original is unchanged.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $q = $this->find($id);
        $title = $q->title;
        $q->delete();

        return redirect('/quotation')->with('quote_status', 'Deleted “'.$title.'”.'.($q->sent_at ? ' A request you already sent stays with our team.' : ''));
    }

    public function print(int $id): View
    {
        $q = $this->find($id);

        return view('pages.quotation.print', ['q' => $q, 'sum' => Quotations::lines($q), 'member' => Members::current(), 'settings' => PublicContent::settings()]);
    }

    private function send(MemberQuote $q): RedirectResponse
    {
        $member = Members::current();
        $back = redirect('/quotation/'.$q->id.'#send');
        $problems = [];
        if (! $q->items) {
            $problems[] = 'Add at least one product or item.';
        }
        if (mb_strlen((string) $q->location) < 2) {
            $problems[] = 'Enter the city or province where the items are needed.';
        }
        $digits = strlen(preg_replace('/\D/', '', (string) $q->phone));
        if ($q->phone === null || ! preg_match('/^[0-9+()\-.\s]+$/', (string) $q->phone) || $digits < 7 || $digits > 15) {
            $problems[] = 'Enter a mobile or phone number we can call, like 0917 123 4567.';
        }
        if ($problems) {
            return $back->with('quote_error', 'Before sending: '.implode(' ', $problems));
        }

        $key = 'quote-send:'.$member->id;
        if (RateLimiter::tooManyAttempts($key, Quotations::SEND_LIMIT)) {
            return $back->with('quote_error', 'You have sent several requests in the last hour. Please wait a little, or contact us by phone or message.');
        }
        if (Inquiry::query()->where('created_at', '>', now()->subDay())->count() >= config('site.inquiry.daily_cap')) {
            return $back->with('quote_error', 'Our inbox is busy right now. Your quotation is saved; please try again later or contact us by phone.');
        }
        RateLimiter::hit($key, 3600);

        $inquiry = Inquiry::query()->create([
            'name' => mb_substr($member->name, 0, 100), 'email' => $member->email, 'phone' => $q->phone,
            'inquiry_type' => 'equipment', 'location' => $q->location,
            'item_label' => mb_substr(Quotations::NAME.' '.$q->reference().': '.$q->title, 0, 200),
            'message' => Quotations::message($q),
        ]);
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('site.inquiry.notify')))));
        if ($to && config('mail.default') !== 'log') {
            try {
                Mail::to($to)->send(new NewInquiry($inquiry));
                $inquiry->forceFill(['emailed_at' => now()])->save();
            } catch (\Throwable $e) {
                Log::error('[quotation] email alert failed', ['inquiry' => $inquiry->id, 'error' => $e::class]);
            }
        }
        $q->forceFill(['sent_at' => now(), 'inquiry_id' => $inquiry->id, 'edited_after_send' => false])->save();

        return $back->with('quote_sent', true);
    }

    private function save(MemberQuote $q, array $items, bool $wasSent, string $before): RedirectResponse
    {
        $q->items = array_values($items);
        if ($wasSent && json_encode([$q->title, $q->location, $q->notes, $q->items]) !== $before) {
            $q->edited_after_send = true;
        }
        $q->save();

        return redirect('/quotation/'.$q->id);
    }

    private function member(): \App\Models\Member
    {
        return Members::current() ?? abort(redirect('/login?next='.rawurlencode('/quotation')));
    }

    /** One of the signed-in member's own quotations; anyone else's is "not found". */
    private function find(int $id): MemberQuote
    {
        $member = Members::current() ?? abort(redirect('/login?next='.rawurlencode('/quotation/'.$id)));
        Quotations::ensureTables();

        return MemberQuote::query()->where('member_id', $member->id)->find($id) ?? abort(404);
    }
}
