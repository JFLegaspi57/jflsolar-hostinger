{{-- Note to every member who has started the Online Course: take it easy and answer in your own words.
     Shown until the date below; each student can close it. --}}
@php
    $ownWordsShow = now('Asia/Manila')->lt(\Illuminate\Support\Carbon::parse('2027-01-31', 'Asia/Manila')) && \App\Support\CourseResults::started($member->id);
    $ownWordsName = 'there';
    if ($ownWordsShow) {
        $parts = preg_split('/\s+/', trim((string) $member->name)) ?: [];
        // "Ma. Cristina" → "Ma. Cristina"; "Warren Canaya" → "Warren"
        $first = $parts[0] ?? '';
        if ($first !== '' && str_ends_with($first, '.') && isset($parts[1])) {
            $first .= ' '.$parts[1];
        }
        $ownWordsName = $first !== '' ? \Illuminate\Support\Str::title(mb_strtolower($first)) : 'there';
    }
@endphp
@if ($ownWordsShow)
    <section id="ann-own-words" aria-labelledby="ann-own-words-h" class="mt-6 rounded-[var(--radius-panel)] border p-5 sm:p-6" style="border-color:#f59e0b;background:#fffbeb">
        <div class="flex items-start justify-between gap-3">
            <h2 id="ann-own-words-h" class="text-lg font-bold text-steel-950">A little note from Prof. JF</h2>
            <button type="button" class="ann-close shrink-0 text-sm font-semibold underline text-steel-800" aria-label="Close this note">Close</button>
        </div>
        <div class="mt-2 space-y-2 text-steel-800">
            <p>Hi {{ $ownWordsName }},</p>
            <p>First of all, thank you for joining the Solar PV System Design course. It makes me really happy to see so many of you learning every day.</p>
            <p>I just want to remind you: take it easy. There's no deadline and no race. The course is free, and it will always be here for you. Read each lesson at your own pace, let it sink in, and go back to it whenever you need to.</p>
            <p>When you answer the "Check your understanding" questions, please use your own words. Short answers are fine, and Taglish is fine. Even a "mali" is fine! A wrong answer in your own words teaches you more than a perfect answer copied from somewhere else, because it shows you exactly what to read again.</p>
            <p>I read your answers myself. After many years of teaching, I can usually tell when an answer comes from an AI tool and when it comes from your own mind. And honestly, the answers from your own mind are the ones that make me proud. 😊</p>
            <p>This course is full of things we learned in real installations here in the Philippines. Some of them you won't find online, and some even correct what the internet usually says. That knowledge is what will keep you, your clients and their homes safe on the roof and at the panel board. It's too valuable to miss by rushing through it.</p>
            <p>So relax, learn it step by step, and enjoy it.</p>
            <p><strong>Learn from the best… learn it the right way!</strong></p>
            <p>JF Legaspi<br>JFL Solar &amp; Renewables Corp.</p>
        </div>
        <p class="mt-3"><a href="{{ \App\Support\Course::PATH }}" class="{{ \App\Support\Ui::button('primary') }}">Go to the course</a></p>
    </section>
    <script>
        (function () {
            var box = document.getElementById('ann-own-words'), key = 'jfl-ann-own-words';
            try { if (localStorage.getItem(key)) { box.remove(); return; } } catch (e) {}
            box.querySelector('.ann-close').addEventListener('click', function () { try { localStorage.setItem(key, '1'); } catch (e) {} box.remove(); });
        })();
    </script>
@endif
