@props(['legend', 'description' => null])
<fieldset class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 sm:p-6">
    <legend class="px-1 text-lg font-bold text-steel-950">{{ $legend }}</legend>
    @if ($description)<p class="mb-4 text-sm text-steel-600">{{ $description }}</p>@endif
    <div class="grid gap-5 sm:grid-cols-2">{{ $slot }}</div>
</fieldset>
