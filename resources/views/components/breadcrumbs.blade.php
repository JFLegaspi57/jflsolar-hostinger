@props(['items'])
<nav aria-label="Breadcrumb" class="text-sm text-steel-600">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $i => $item)
            <li class="flex items-center gap-1.5">
                @if ($i > 0)<span aria-hidden="true">/</span>@endif
                @if (! empty($item['href']))
                    <a href="{{ $item['href'] }}" class="hover:text-steel-950 hover:underline">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="font-semibold text-steel-900">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
