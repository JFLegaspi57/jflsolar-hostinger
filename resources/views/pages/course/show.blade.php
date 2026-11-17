@php
    use App\Support\Course;
    use App\Support\Ui;
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8';
    $peso = fn ($v) => '₱'.number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
    $phone = $settings->phone_display ?? '';
@endphp
<x-site-layout :title="'Online course: '.Course::TITLE" :path="Course::PATH"
    description="Learn to design solar PV systems the right way with JF Legaspi, in a free online course for JFL Solar members.">
    @php $banner = Course::banner('course'); $heroIntro = 'Learn from the best... learn it the right way! A free step-by-step online course by JF Legaspi, for JFL Solar members.'; @endphp
    @if ($banner)
        <x-page-hero eyebrow="Online course" :title="Course::TITLE" :intro="$heroIntro" :image="$banner['src']" :srcset="$banner['srcset']" imageAlt="Solar technicians installing solar panels on a large roof under a clear sky" focus="50% 40%" />
    @else
        <x-page-header eyebrow="Online course" :title="Course::TITLE" :intro="$heroIntro" />
    @endif
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
                    @if ($member && $canView) You're in: all lessons are open @elseif ($canView) Staff preview @else Free for JFL Solar members @endif
                </h2>
                @if ($member && $canView)
                    <p class="mt-2 text-steel-700">The course is free. Start with Lesson 1 below and go at your own pace. Each lesson is in English and Tagalog.</p>
                    <p class="mt-4 border-l-4 border-gold-500 bg-gold-50 p-4 text-steel-800" style="border-radius: .6rem"><span aria-hidden="true">🎓 </span><strong>Certificate of completion:</strong> available on request. It is issued after an assessment of your learning and a number of on-the-job training (OJT) sessions with the JFL Solar technical team.</p>
                @elseif ($canView)
                    <p class="mt-2 text-steel-700">You are signed in as staff, so you can open the lessons (also drafts) to check them.</p>
                @else
                    <p class="mt-2 text-steel-700">The whole course is <strong>free</strong>. Sign in, or create a free JFL Solar account in a minute, to open the lessons, slides and notes.</p>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <a href="/signup?next={{ rawurlencode(Course::PATH) }}" class="{{ Ui::button('primary') }}">Create a free account</a>
                        <a href="/login?next={{ rawurlencode(Course::PATH) }}" class="{{ Ui::button('secondary') }}">Sign in</a>
                    </div>
                @endif
            </section>
            @include('pages.course.donate')

            <section class="{{ $card }}" aria-labelledby="about-h">
                <h2 id="about-h" class="text-xl font-bold text-steel-950">About this course</h2>
                <p class="mt-3 text-steel-800">How do you size and design a solar PV system that works for years, not just on paper? This course starts from the very source, the sun, and builds up step by step to designing complete solar PV systems.</p>
                <p class="mt-3 text-steel-800">It is taught by JF Legaspi, founder of JFL Solar and former CTO of a European solar company, who designed commercial and industrial solar PV systems for more than three decades and is a regular resource person at solar PV seminars across the Philippines.</p>
                <p class="mt-3 text-steel-800">Each lesson combines the original training slides with clear notes and worked calculations you can follow at your own pace, on your phone or computer.</p>
            </section>

            <section class="{{ $card }}" aria-labelledby="lessons-h">
                <h2 id="lessons-h" class="text-xl font-bold text-steel-950">Lessons</h2>
                @if (! $canView)
                    {{-- Visitors who are not signed in never see the lessons themselves, only what the course covers. --}}
                    <p class="mt-3 text-steel-800">The course goes step by step: from how solar energy and solar panels work, to the types of solar PV systems, the tools and equipment, reading panel datasheets and temperature data, and on to sizing and designing complete solar PV systems.</p>
                    <p class="mt-3 text-steel-800">Every lesson is in <strong>English and Tagalog</strong>, with slides, clear notes, worked calculations and questions to check your understanding. New lessons are added regularly.</p>
                    <p class="mt-3 font-semibold text-steel-950">Sign in or create a free account to see the lessons.</p>
                @elseif ($lessons->isEmpty())
                    <p class="mt-3 text-steel-700">The first lesson is being prepared.</p>
                @else
                    @if ($results)
                        @php
                            $withQuiz = collect($results)->filter(fn ($r) => $r['total'] > 0);
                            $passedN = $withQuiz->where('status', 'passed')->count();
                        @endphp
                        <div class="mt-4 border-l-4 border-gold-500 bg-gold-50 p-4 text-steel-800" style="border-radius: .6rem{{ $passedN === $withQuiz->count() && $passedN > 0 ? '; border-color: #16a34a; background: #ecfdf3' : '' }}">
                            <p class="font-bold text-steel-950">Your progress: {{ $passedN }} of {{ $withQuiz->count() }} lessons passed</p>
                            @if ($passedN === $withQuiz->count() && $passedN > 0)
                                <p class="mt-1">🎉 Congratulations, you have passed every lesson! You can now request the assessment and OJT sessions for your certificate of completion.</p>
                            @else
                                <p class="mt-1">A lesson is passed with at least {{ \App\Support\CourseResults::PASS_PERCENT }}% on its "Check your understanding" questions.</p>
                            @endif
                        </div>
                    @endif
                    <ol class="mt-4 divide-y divide-steel-200">
                        @foreach ($lessons as $l)
                            <li class="flex gap-4 py-4">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-steel-900 font-bold text-white">{{ $loop->iteration }}</span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ Course::PATH }}/{{ $l->slug }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $l->title }}</a>
                                    @php $res = $results[$l->id] ?? null; @endphp
                                    @if ($res && $res['status'] === 'passed')<span class="ml-1 inline-block rounded-full px-2 text-xs font-bold" style="background:#dcfce7;color:#166534;vertical-align:middle;padding-block:.15rem">✅ Passed · {{ $res['percent'] }}%</span>
                                    @elseif ($res && $res['status'] === 'failed')<span class="ml-1 inline-block rounded-full px-2 text-xs font-bold" style="background:#fee2e2;color:#991b1b;vertical-align:middle;padding-block:.15rem">❌ Failed · {{ $res['percent'] }}%</span>
                                    @elseif ($res && $res['status'] === 'progress')<span class="ml-1 inline-block rounded-full bg-steel-100 px-2 text-xs font-bold text-steel-700" style="vertical-align:middle;padding-block:.15rem">{{ $res['answered'] }} of {{ $res['total'] }} checked</span>@endif
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
