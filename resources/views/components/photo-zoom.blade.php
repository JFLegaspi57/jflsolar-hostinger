@props(['full', 'small', 'label' => 'View the full photo', 'alt' => 'Profile photo'])
{{-- Wraps a small profile photo: clicking it opens the whole photo in a pop-up ($small is shown if the full one is missing). --}}
<button type="button" data-photo-zoom="{{ $full }}" data-photo-small="{{ $small }}" data-photo-alt="{{ $alt }}" aria-label="{{ $label }}" title="{{ $label }}"
    {{ $attributes->merge(['class' => 'photo-zoom-btn shrink-0']) }}>{{ $slot }}</button>
@once
    <style>
        .photo-zoom-btn { padding: 0; border: 0; background: none; border-radius: 9999px; cursor: zoom-in; line-height: 0; }
        .photo-zoom-btn:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
        #photo-zoom { position: fixed; inset: 0; margin: auto; padding: 0; border: 0; background: transparent; width: fit-content; height: fit-content; max-width: min(92vw, 1000px); max-height: 92vh; overflow: visible; }
        #photo-zoom::backdrop { background: rgba(10, 15, 25, .82); }
        #photo-zoom img { display: block; max-width: min(92vw, 1000px); max-height: 86vh; width: auto; height: auto; border-radius: .75rem; background: #fff; box-shadow: 0 20px 50px rgba(0, 0, 0, .45); }
        #photo-zoom .pz-close { position: absolute; top: -.9rem; right: -.9rem; width: 2.75rem; height: 2.75rem; border-radius: 9999px; border: 0; background: #fff; color: #111827; font-size: 1.5rem; line-height: 1; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 0, 0, .3); }
    </style>
    <dialog id="photo-zoom" aria-label="Profile photo">
        <img src="" alt="">
        <button type="button" class="pz-close" aria-label="Close">×</button>
    </dialog>
    <script>
        (function () {
            var d = document.getElementById('photo-zoom'), img = d.querySelector('img');
            document.addEventListener('click', function (e) {
                var b = e.target.closest('[data-photo-zoom]');
                if (!b) return;
                e.preventDefault();
                img.onerror = function () { if (b.dataset.photoSmall && img.getAttribute('src') !== b.dataset.photoSmall) img.src = b.dataset.photoSmall; };
                img.alt = b.dataset.photoAlt || '';
                img.src = b.dataset.photoZoom;
                d.showModal();
            });
            d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('.pz-close')) d.close(); });
        })();
    </script>
@endonce
