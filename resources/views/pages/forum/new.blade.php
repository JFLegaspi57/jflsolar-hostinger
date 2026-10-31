@php
    use App\Support\Forum;
    use App\Support\Ui;
    $input = 'w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700';
    $err = fn ($k) => $errors->first($k) ?: null;
    $staff = Forum::staff();
    $member = \App\Support\Members::current();
@endphp
<x-site-layout title="Ask a question — Solar forum" path="/forum/new" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-3xl">
            <p><a href="/forum" class="font-semibold text-steel-800 underline">← Solar forum</a></p>
            <h1 class="mt-3 text-3xl font-extrabold text-steel-950 sm:text-4xl">Ask a question</h1>
            <p class="mt-3 text-lg text-steel-700">Tell us your setup (panels, inverter, battery, how you use it) so people can give a useful answer.</p>
            <form method="post" action="/forum" class="mt-6 space-y-5 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8">
                @csrf
                <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label></div>
                <x-form-field id="n-category" label="Topic" :error="$err('category')" required>
                    <select id="n-category" name="category" required @if ($err('category')) aria-invalid="true" aria-describedby="n-category-error" @endif class="{{ $input }} min-h-12">
                        <option value="">Choose…</option>
                        @foreach (Forum::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-form-field>
                <x-form-field id="n-title" label="Title" hint="One line, e.g. “Which battery works with a 6 kW hybrid inverter?”" :error="$err('title')" required>
                    <input id="n-title" name="title" type="text" value="{{ old('title') }}" maxlength="140" required aria-describedby="n-title-hint{{ $err('title') ? ' n-title-error' : '' }}" @if ($err('title')) aria-invalid="true" @endif class="{{ $input }} min-h-12">
                </x-form-field>
                <x-form-field id="n-body" label="Your question" :error="$err('body')" required>
                    <textarea id="n-body" name="body" rows="8" maxlength="5000" required @if ($err('body')) aria-invalid="true" aria-describedby="n-body-error" @endif class="{{ $input }} py-3">{{ old('body') }}</textarea>
                </x-form-field>
                <p class="text-sm text-steel-600">Posting as <span class="font-semibold">{{ $staff ? Forum::STAFF_NAME : Forum::displayName($member?->name ?? '') }}</span>. Plain text; up to {{ Forum::MAX_LINKS }} links.@if (! $staff && $member && ! $member->forum_trusted) Your first post appears after a quick check by our team.@endif</p>
                <button type="submit" class="{{ Ui::button('primary', 'lg') }}">Post question</button>
            </form>
        </div>
    </x-container>
</x-site-layout>
