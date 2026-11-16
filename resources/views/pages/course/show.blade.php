@php
    use App\Support\Course;
    use App\Support\Ui;
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8';
    $peso = fn ($v) => '₱'.number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $phone = $settings->phone_display ?? '';
@endphp
<x-site-layout :title="'Online course: '.Course::TITLE" :path="Course::PATH"
    description="Learn to design solar PV systems the right way with JF Legaspi, in an online course for JFL Solar paid members.">
    <x-page-header eyebrow="Online course" :title="Course::TITLE" intro="Learn from the best... learn it the right way! A step-by-step online course by JF Legaspi, for JFL Solar paid members." />
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-4xl space-y-8">
            @include('partials.account-flash')
            @if ($paidNote === 'paid')
                <p role="status" class="rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">Thank you! Your payment is confirmed and the course is unlocked{{ $paidUntil ? ' until '.$paidUntil->timezone('Asia/Manila')->format('j F Y') : '' }}.</p>
            @elseif ($paidNote === 'pending')
                <p role="status" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 p-4 font-semibold text-warn-800">Thank you! We are waiting for PayMongo to confirm your payment. This usually takes a minute: reload this page shortly.</p>
            @elseif ($cancelled)
                <p role="status" class="rounded-[var(--radius-control)] border border-steel-200 bg-steel-50 p-4 text-steel-800">The payment was cancelled. You can try again any time.</p>
            @endif

            <section class="{{ $card }}" aria-labelledby="access-h">
                <h2 id="access-h" class="text-xl font-bold text-steel-950">
                    @if ($canView && $paidUntil && $paidUntil->isFuture()) Your paid membership is active @elseif ($canView) Staff preview @else Paid members only @endif
                </h2>
                @if ($canView && $paidUntil && $paidUntil->isFuture())
                    <p class="mt-2 text-steel-700">You have full access to every lesson until <strong>{{ $paidUntil->timezone('Asia/Manila')->format('j F Y') }}</strong>.</p>
                @elseif ($canView)
                    <p class="mt-2 text-steel-700">You are signed in as staff, so you can open the lessons (also drafts) to check them.</p>
                @else
                    <p class="mt-2 text-steel-700">The lessons, slides and notes are open only to JFL Solar <strong>paid members</strong>. A paid membership gives you one year of access to the whole course, including new lessons added during that year.</p>
                    @if ($price)<p class="mt-3 text-2xl font-extrabold text-steel-950">{{ $peso($price) }} <span class="text-base font-semibold text-steel-600">for 1 year</span></p>@endif
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        @if (! $member)
                            <a href="/login?next={{ rawurlencode(Course::PATH) }}" class="{{ Ui::button('primary') }}">Sign in to join</a>
                            <a href="/signup?next={{ rawurlencode(Course::PATH) }}" class="{{ Ui::button('secondary') }}">Create an account</a>
                        @elseif ($canBuy)
                            <form method="post" action="/courses/join">@csrf<button type="submit" class="{{ Ui::button('primary') }}">Join as a paid member</button></form>
                            <span class="text-sm text-steel-600">Pay securely with GCash, Maya, card or QR Ph through PayMongo. The course unlocks as soon as the payment is confirmed.</span>
                        @else
                            <p class="text-steel-700">Online enrolment opens soon. To join now, call or message our Sales team{{ $phone ? ' at '.$phone : '' }}.</p>
                        @endif
                    </div>
                    @if ($member && $paidUntil && $paidUntil->isPast())<p class="mt-3 text-sm text-steel-600">Your previous membership ended on {{ $paidUntil->timezone('Asia/Manila')->format('j F Y') }}.</p>@endif
                @endif
            </section>

            <section class="{{ $card }}" aria-labelledby="about-h">
                <h2 id="about-h" class="text-xl font-bold text-steel-950">About this course</h2>
                <p class="mt-3 text-steel-800">How do you size and design a solar PV system that works for years, not just on paper? This course starts from the very source, the sun, and builds up step by step to designing complete solar PV systems.</p>
                <p class="mt-3 text-steel-800">It is taught by JF Legaspi, founder of JFL Solar and former CTO of a European solar company, who designed commercial and industrial solar PV systems for more than three decades and is a regular resource person at solar PV seminars across the Philippines.</p>
                <p class="mt-3 text-steel-800">Each lesson combines the original training slides with clear notes and worked calculations you can follow at your own pace, on your phone or computer.</p>
            </section>

            <section class="{{ $card }}" aria-labelledby="lessons-h">
                <h2 id="lessons-h" class="text-xl font-bold text-steel-950">Lessons</h2>
                @if ($lessons->isEmpty())
                    <p class="mt-3 text-steel-700">The first lesson is being prepared.</p>
                @else
                    <ol class="mt-4 divide-y divide-steel-200">
                        @foreach ($lessons as $l)
                            <li class="flex gap-4 py-4">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-steel-900 font-bold text-white">{{ $loop->iteration }}</span>
                                <div class="min-w-0 flex-1">
                                    @if ($canView)
                                        <a href="{{ Course::PATH }}/{{ $l->slug }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $l->title }}</a>
                                    @else
                                        <p class="text-lg font-bold text-steel-950">{{ $l->title }} <span class="ml-1 inline-flex items-center rounded-full border border-steel-300 px-2 text-xs font-semibold text-steel-700">Paid members</span></p>
                                    @endif
                                    @if ($l->summary)<p class="mt-1 text-steel-700">{{ $l->summary }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-2 text-sm text-steel-600">More lessons on designing solar PV systems will be added.</p>
                @endif
            </section>
        </div>
    </x-container>
</x-site-layout>
