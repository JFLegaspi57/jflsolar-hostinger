@props(['action', 'form', 'hidden' => [], 'status' => null, 'isNew' => false, 'publishable' => true, 'saveLabel' => null])
{{--
  Admin edit form: restores every field after a failed save (old input), shows
  and focuses an error summary, warns about unsaved changes, and offers
  Save / Save and publish / Save and unpublish.
--}}
@php
    $live = $status === 'published';
    $errorList = $form->errors();
    $formError = session('form_error');
@endphp
<form method="post" action="{{ $action }}" novalidate data-admin-form @if ($form->failed()) data-dirty-on-load @endif class="space-y-8 pb-36">
    @csrf
    @foreach ($hidden as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach

    @if ($formError)
        <div tabindex="-1" role="alert" data-focus-on-load class="rounded-[var(--radius-control)] border-2 border-danger-700 bg-danger-50 p-4 focus:outline-none">
            <p class="font-bold text-danger-700">{{ $formError }}</p>
            @if ($errorList)
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach ($errorList as $field => $msg)<li><a href="#f-{{ $field }}" class="text-danger-700 underline">{{ $msg }}</a></li>@endforeach
                </ul>
            @endif
        </div>
    @endif
    @if ($live && ! $isNew && $publishable)
        <p class="rounded-[var(--radius-control)] border border-gold-200 bg-gold-50 px-4 py-3 text-sm text-steel-900"><strong>This item is live.</strong> Saving updates the public page immediately. To take it offline while you work, use “Save and unpublish”.</p>
    @endif

    {{ $slot }}

    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-steel-200 bg-white/95 backdrop-blur lg:left-64">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-2 px-4 py-3 sm:px-6">
            <p class="mr-auto text-sm text-steel-600" aria-live="polite" data-save-state data-clean-text="{{ $isNew ? 'New item — not saved yet' : 'All changes saved' }}">{{ $form->failed() ? 'Unsaved changes' : ($isNew ? 'New item — not saved yet' : 'All changes saved') }}</p>
            <button type="submit" name="intent" value="save" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-5 font-semibold text-white hover:bg-steel-800 disabled:opacity-60">{{ $saveLabel ?? ($isNew ? 'Save as draft' : 'Save') }}</button>
            @if ($publishable)
                @if ($live)
                    <button type="submit" name="intent" value="unpublish" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-2 border-steel-900 px-4 font-semibold text-steel-900 hover:bg-steel-50 disabled:opacity-60">Save and unpublish</button>
                @else
                    <button type="submit" name="intent" value="publish" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-gold-500 px-5 font-semibold text-steel-950 hover:bg-gold-400 disabled:opacity-60">Save and publish</button>
                @endif
            @endif
        </div>
    </div>
</form>
