{{-- Pop-up: the GCash reference number the member sent was rejected (no matching payment). Needs $cert. --}}
@php use App\Support\CourseCertificates as Certs; @endphp
            <dialog id="cert-warn-{{ $cert->id }}" aria-labelledby="cert-warn-h-{{ $cert->id }}" class="rounded-[var(--radius-panel)] p-0 shadow-2xl" style="max-width:30rem;border:2px solid #dc2626">
                <div class="p-6">
                    <h2 id="cert-warn-h-{{ $cert->id }}" class="text-xl font-bold" style="color:#991b1b">⚠️ Warning {{ min($cert->wrong_refs, Certs::MAX_WRONG_REFS) }} of {{ Certs::MAX_WRONG_REFS }}: wrong GCash reference number</h2>
                    <p class="mt-3 text-steel-800">The GCash reference number you sent, <strong>{{ $cert->wrong_last }}</strong>, does not match any payment we received.</p>
                    @if (Certs::blocked($cert))
                        <p class="mt-2 font-semibold text-steel-900">This was the {{ Certs::MAX_WRONG_REFS }}rd wrong reference number, so you can no longer send one on the website. If you really paid, please call our Sales team at 0969 471 2098 with your GCash receipt.</p>
                    @else
                        <p class="mt-2 text-steel-800">Please check your GCash receipt and type the reference number exactly as shown. Sending a made-up number won't unlock your certificate.</p>
                        <p class="mt-2 font-semibold text-steel-900">You have {{ Certs::MAX_WRONG_REFS - (int) $cert->wrong_refs }} {{ Certs::MAX_WRONG_REFS - (int) $cert->wrong_refs === 1 ? 'try' : 'tries' }} left. After {{ Certs::MAX_WRONG_REFS }} wrong reference numbers, you can no longer send one here.</p>
                    @endif
                    <form method="post" action="/account/certificate/warning-seen" class="mt-5">@csrf<button type="submit" class="{{ \App\Support\Ui::button('primary') }}">I understand</button></form>
                </div>
            </dialog>
            <script>(function () { var d = document.getElementById('cert-warn-{{ $cert->id }}'); if (d && d.showModal) { d.showModal(); } else if (d) { d.setAttribute('open', ''); } })();</script>
