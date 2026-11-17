{{-- One slide inside a lesson. The watermark (the company name) marks the slide as JFL Solar's. --}}
<figure class="course-slide" data-slide>
    <div class="course-slide-frame">
        <img src="/courses/slide/{{ $slide->id }}" alt="Slide {{ $number }}{{ $slide->caption ? ': '.$slide->caption : '' }}" width="{{ $slide->width ?: 1600 }}" height="{{ $slide->height ?: 900 }}" loading="lazy" decoding="async" draggable="false">
        @if ($watermark)<span class="course-wm" aria-hidden="true">{{ $watermark }}</span>@endif
    </div>
    @if ($slide->caption)<figcaption>{{ $slide->caption }}</figcaption>@endif
</figure>
