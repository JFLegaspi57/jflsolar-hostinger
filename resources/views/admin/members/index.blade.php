@php $input = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-400 bg-white px-3 text-base text-steel-950'; @endphp
<x-admin.layout title="Members">
    <x-admin.page-title title="Members" intro="Customer accounts created on the website (Sign up). Members see their orders, check out faster and can post in the forum. Disabling an account signs the person out and stops them signing in." />
    <form method="get" action="/admin/members" class="mb-4 flex max-w-xl gap-2">
        <label for="m-q" class="sr-only">Search members</label>
        <input id="m-q" name="q" type="search" value="{{ $q }}" placeholder="Search name, email or phone" class="{{ $input }}">
        <button type="submit" class="{{ \App\Support\Ui::button('secondary') }}">Search</button>
    </form>
    @if (! $members || $members->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $q !== '' ? 'No members match your search.' : 'No one has created an account yet.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($members as $m)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="font-bold text-steel-950">{{ $m->name }} @if ($m->status !== 'active')<span class="ml-1 rounded-full bg-danger-50 px-2 text-xs font-semibold text-danger-700">Disabled</span>@endif</p>
                        <p class="text-sm text-steel-700 break-all"><a href="mailto:{{ $m->email }}" class="underline">{{ $m->email }}</a>@if ($m->phone) · <span class="tabular">{{ $m->phone }}</span>@endif</p>
                        <p class="text-sm text-steel-600">Joined {{ $m->created_at->timezone('Asia/Manila')->format('j M Y') }}@isset($m->orders_count) · {{ $m->orders_count }} {{ $m->orders_count === 1 ? 'order' : 'orders' }}@endisset @if ($m->last_login_at)· last signed in {{ $m->last_login_at->timezone('Asia/Manila')->format('j M Y') }}@endif</p>
                    </div>
                    <form method="post" action="/admin/members/{{ $m->id }}{{ $q !== '' ? '?q='.rawurlencode($q) : '' }}">
                        @csrf
                        @if ($m->status === 'active')
                            <button type="submit" name="action" value="disable" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Disable account</button>
                        @else
                            <button type="submit" name="action" value="enable" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Enable account</button>
                        @endif
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $members->links() }}</div>
    @endif
</x-admin.layout>
