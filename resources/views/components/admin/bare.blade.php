@props(['title'])
{{-- Minimal page for sign-in, password and recovery screens. --}}
<!DOCTYPE html>
<html lang="en-PH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | JFL Solar admin</title>
    <link rel="icon" href="/icon.png" sizes="192x192" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="flex min-h-dvh flex-col bg-steel-50 antialiased">
    <main id="main" class="flex flex-1 items-center justify-center px-4 py-16">
        <div class="w-full max-w-md rounded-[var(--radius-panel)] border border-steel-200 bg-white p-8 shadow-sm">
            <x-logo />
            {{ $slot }}
        </div>
    </main>
</body>
</html>
