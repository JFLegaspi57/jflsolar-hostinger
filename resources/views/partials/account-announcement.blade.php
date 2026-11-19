{{-- Announcement on the member account page (shown until the date below; each member can close it). --}}
@if (now('Asia/Manila')->lt(\Illuminate\Support\Carbon::parse('2026-12-04', 'Asia/Manila')))
    <section id="ann-quiz-fixed" aria-labelledby="ann-quiz-fixed-h" class="mt-6 rounded-[var(--radius-panel)] border p-5 sm:p-6" style="border-color:#16a34a;background:#f0fdf4">
        <div class="flex items-start justify-between gap-3">
            <h2 id="ann-quiz-fixed-h" class="text-lg font-bold text-steel-950">✅ Fixed: "Check my answer" in the Online Course</h2>
            <button type="button" class="ann-close shrink-0 text-sm font-semibold underline text-steel-800" aria-label="Close this announcement">Close</button>
        </div>
        <p class="mt-2 text-steel-800">Some of you saw <em>"Your answer can't be checked right now"</em> when checking answers. Thank you for telling us. It's now fixed: checking works even when many students check at the same time, and the page tries again by itself if the checker is busy.</p>
        <p class="mt-2 text-steel-800"><strong>What to do:</strong> refresh the lesson and press <strong>"Check my answer"</strong> on the questions that weren't checked. Your typed answers are still saved.</p>
        <p class="mt-2 text-steel-700"><em>Naayos na po. Paki-refresh ang lesson at pindutin ulit ang "Check my answer" sa mga tanong na hindi pa na-check.</em></p>
        <p class="mt-3"><a href="{{ \App\Support\Course::PATH }}" class="{{ \App\Support\Ui::button('primary') }}">Go to the course</a></p>
    </section>
    <script>
        (function () {
            var box = document.getElementById('ann-quiz-fixed'), key = 'jfl-ann-quiz-fixed';
            try { if (localStorage.getItem(key)) { box.remove(); return; } } catch (e) {}
            box.querySelector('.ann-close').addEventListener('click', function () { try { localStorage.setItem(key, '1'); } catch (e) {} box.remove(); });
        })();
    </script>
@endif
