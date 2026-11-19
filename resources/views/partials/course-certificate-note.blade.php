{{-- How to get the certificate of completion (course page and Your account → Online Course). --}}
{{-- Hidden once the student has passed every lesson. --}}
@php $noteMember = \App\Support\Members::current(); @endphp
@unless ($noteMember && (\App\Support\CourseResults::nextStep($noteMember->id)['kind'] ?? '') === 'complete')
<p class="{{ $class ?? 'mt-4' }} border-l-4 border-gold-500 bg-gold-50 p-4 text-steel-800" style="border-radius: .6rem"><span aria-hidden="true">🎓 </span><strong>Certificate of completion:</strong> available on request for a fee of ₱2,500. To qualify, you must complete the whole course on your own, with every answer written in your own words, without the aid of AI tools like ChatGPT or similar apps. It is issued after an assessment of your learning and a number of on-the-job training (OJT) sessions as your hands-on training from any solar installation company near you.</p>
@endunless
