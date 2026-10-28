@php
    $me = auth()->user();
    $add = $errors->getBag('add');
    $input = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base aria-[invalid=true]:border-danger-700';
    $temp = session('temporary');
@endphp
<x-admin.layout title="Staff">
    <x-admin.page-title title="Staff access" intro="Owners can manage content and the staff list. Editors manage content only. There is no email involved: give new staff their temporary password yourself, in person or by message." />

    @if ($temp)
        <div role="status" data-focus-on-load tabindex="-1" class="mb-6 rounded-[var(--radius-panel)] border-2 border-gold-500 bg-gold-50 p-5 focus:outline-none">
            <p class="font-bold text-steel-950">{{ $temp['new'] ? 'Account created for '.$temp['email'].'.' : 'Password reset for '.$temp['email'].'.' }}</p>
            <p class="mt-1 text-steel-800">Temporary password (shown only once — copy it now):</p>
            <p class="mt-2 inline-block rounded bg-white px-3 py-2 font-mono text-lg font-bold tracking-wide text-steel-950 select-all">{{ $temp['password'] }}</p>
            <p class="mt-2 text-sm text-steel-700">Give it to the person directly. They sign in at <strong>{{ \App\Support\Site::url('/admin') }}</strong> and must choose their own password straight away.</p>
        </div>
    @endif

    <div class="space-y-8">
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($staff as $r)
                @php
                    $self = $r->id === $me->id;
                    $toRole = $r->role === 'owner' ? 'editor' : 'owner';
                @endphp
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="font-bold break-all text-steel-950">{{ $r->name }} @if ($self)<span class="text-sm font-normal text-steel-600">(you)</span>@endif</p>
                        <p class="text-sm text-steel-600">{{ $r->email }} · <span class="capitalize">{{ $r->role }}</span> · {{ $r->active ? 'Active' : 'Deactivated' }}@if ($r->must_change_password) · waiting for first sign-in @endif @if ($r->last_login_at) · last signed in {{ $r->last_login_at->timezone('Asia/Manila')->format('j M Y') }}@endif</p>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <x-admin.confirm tone="caution" :action="'/admin/staff/'.$r->id.'/role'" :fields="['role' => $toRole]" :title="'Make '.$r->email.' '.($toRole === 'owner' ? 'an owner' : 'an editor').'?'" :confirm-label="'Make '.$toRole"
                            :label="'Make '.$toRole" :sr-label="$r->email">
                            <p>{{ $toRole === 'owner' ? 'Owners can manage content and the staff list, including removing other owners.' : 'Editors can manage content but cannot change the staff list. If this is you, you will lose access to this page immediately.' }}</p>
                            @if ($self)<p class="mt-2 font-semibold text-danger-700">This is your own account.</p>@endif
                        </x-admin.confirm>
                        <x-admin.confirm tone="caution" :action="'/admin/staff/'.$r->id.'/active'" :fields="['active' => $r->active ? 0 : 1]" :title="($r->active ? 'Deactivate ' : 'Reactivate ').$r->email.'?'" :confirm-label="$r->active ? 'Deactivate' : 'Reactivate'"
                            :label="$r->active ? 'Deactivate' : 'Reactivate'" :sr-label="$r->email">
                            <p>{{ $r->active ? 'They are signed out and lose admin access immediately, but stay on the list so an owner can reactivate them later.' : 'They will be able to sign in to the admin again with their password.' }}</p>
                            @if ($self)<p class="mt-2 font-semibold text-danger-700">This is your own account.</p>@endif
                        </x-admin.confirm>
                        @unless ($self)
                            <x-admin.confirm tone="caution" :action="'/admin/staff/'.$r->id.'/reset'" :title="'Reset the password for '.$r->email.'?'" confirm-label="Reset password"
                                label="Reset password" :sr-label="$r->email">
                                <p>Their current password stops working and they are signed out. You will see a new temporary password to give them; they choose their own at next sign-in.</p>
                            </x-admin.confirm>
                        @endunless
                        <x-admin.confirm :action="'/admin/staff/'.$r->id.'/delete'" :title="'Remove '.$r->email.'?'" confirm-label="Remove access"
                            label="Remove" :sr-label="$r->email">
                            <p>They lose admin access immediately and are removed from the list.</p>
                            @if ($self)<p class="mt-2 font-semibold text-danger-700">This is your own account.</p>@endif
                        </x-admin.confirm>
                    </div>
                </li>
            @endforeach
        </ul>

        <form method="post" action="/admin/staff" class="grid gap-4 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 sm:grid-cols-3 sm:items-end">
            @csrf
            <h2 class="text-lg font-bold text-steel-950 sm:col-span-3">Add a staff member</h2>
            @if (session('form_error'))<p role="alert" class="text-sm font-semibold text-danger-700 sm:col-span-3">{{ session('form_error') }}</p>@endif
            <div>
                <label for="s-name" class="mb-1 block font-semibold">Name <span class="text-sm font-normal text-steel-600">(required)</span></label>
                <input id="s-name" name="name" value="{{ old('name') }}" maxlength="120" class="{{ $input }}" @if ($add->has('name')) aria-invalid="true" aria-describedby="s-name-error" @endif>
                @if ($add->has('name'))<p id="s-name-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $add->first('name') }}</p>@endif
            </div>
            <div>
                <label for="s-email" class="mb-1 block font-semibold">Email <span class="text-sm font-normal text-steel-600">(required)</span></label>
                <input id="s-email" name="email" type="email" value="{{ old('email') }}" maxlength="254" class="{{ $input }}" @if ($add->has('email')) aria-invalid="true" aria-describedby="s-email-error" @endif>
                @if ($add->has('email'))<p id="s-email-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $add->first('email') }}</p>@endif
            </div>
            <div>
                <label for="s-role" class="mb-1 block font-semibold">Role</label>
                <select id="s-role" name="role" class="{{ $input }}">
                    <option value="editor" @selected(old('role', 'editor') === 'editor')>Editor — manages content</option>
                    <option value="owner" @selected(old('role') === 'owner')>Owner — content and staff</option>
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-5 font-semibold text-white hover:bg-steel-800">Add staff member</button>
            </div>
        </form>
        <p class="text-sm text-steel-600">The last active owner cannot be removed, deactivated or demoted. A locked-out owner can use the recovery page (it needs access to the website's files in Hostinger).</p>
    </div>
</x-admin.layout>
