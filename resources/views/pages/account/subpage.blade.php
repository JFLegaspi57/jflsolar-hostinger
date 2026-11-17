{{-- One section of the member's account on its own page (Your Orders, Online Course), opened from the Account menu. --}}
@php
    use App\Support\Ui;
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8';
@endphp
<x-site-layout :title="$heading" :path="$path" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-3xl">
            <a href="/account" class="inline-flex min-h-11 items-center text-sm font-semibold text-steel-700 underline hover:text-steel-950">← Your account</a>
            <p class="mt-2 text-sm font-semibold tracking-wide text-steel-600 uppercase">Your account</p>
            <h1 class="mt-1 text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $heading }}</h1>
            @if ($section === 'course')
                @include('pages.course.continue', ['nextStep' => $nextStep ?? null, 'class' => 'mt-6'])
            @endif
            <section class="{{ $card }} mt-8">
                @if ($section === 'orders')
                    @include('pages.account.orders-list')
                @else
                    <p class="text-sm font-semibold tracking-wide text-gold-800 uppercase">Online Course</p>
                    <h2 class="mt-1 text-xl font-bold text-steel-950">Solar PV System Designing</h2>
                    <p class="mt-3 text-steel-700">Learn to design solar PV systems with JF Legaspi. Free for members, in English and Tagalog.</p>
                    @php $courseStarted = ($nextStep['kind'] ?? 'start') !== 'start'; @endphp
                    @include('pages.course.record', ['record' => $courseRecord, 'started' => $courseStarted])
                    <p class="mt-4"><a href="{{ \App\Support\Course::PATH }}" class="{{ Ui::button('primary') }}">{{ $courseStarted ? 'Open the course' : 'Start the Course' }}</a></p>
                @endif
            </section>
        </div>
    </x-container>
</x-site-layout>
