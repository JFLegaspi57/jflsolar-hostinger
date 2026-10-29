{{-- 3D tilt for banner photos: the photo leans toward the mouse, zooms slightly and shows a soft
     light reflection. Mouse/trackpad only; phones and "reduce motion" users get the still photo.
     Markup needed: section.hero-tilt[data-hero-tilt] > .hero-media > img + .hero-glare --}}
<style>
    .hero-tilt .hero-media { overflow: hidden; perspective: 1100px; }
    .hero-tilt .hero-glare { opacity: 0; pointer-events: none; }
    @media (hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference) {
        .hero-tilt .hero-media img {
            transform: rotateX(var(--tilt-y, 0deg)) rotateY(var(--tilt-x, 0deg)) translate3d(var(--shift-x, 0px), var(--shift-y, 0px), 0) scale(var(--tilt-zoom, 1));
            transition: transform .7s cubic-bezier(.2, .7, .2, 1);
            will-change: transform;
        }
        .hero-tilt.is-tilting .hero-media img { transition-duration: .15s; }
        .hero-tilt .hero-glare {
            background: radial-gradient(circle at var(--glare-x, 50%) var(--glare-y, 50%), rgb(255 255 255 / .22), transparent 45%);
            mix-blend-mode: soft-light; transition: opacity .5s;
        }
        .hero-tilt.is-tilting .hero-glare { opacity: 1; }
    }
</style>
<script>
    (function () {
        var hero = document.querySelector('[data-hero-tilt]');
        if (!hero || !window.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)').matches) return;
        var frame = 0, px = 0, py = 0;
        function apply() {
            frame = 0;
            hero.style.setProperty('--tilt-x', (px * 7).toFixed(2) + 'deg');
            hero.style.setProperty('--tilt-y', (-py * 5).toFixed(2) + 'deg');
            hero.style.setProperty('--shift-x', (-px * 18).toFixed(1) + 'px');
            hero.style.setProperty('--shift-y', (-py * 12).toFixed(1) + 'px');
            hero.style.setProperty('--glare-x', ((px + 0.5) * 100).toFixed(1) + '%');
            hero.style.setProperty('--glare-y', ((py + 0.5) * 100).toFixed(1) + '%');
        }
        hero.addEventListener('pointermove', function (e) {
            var r = hero.getBoundingClientRect();
            px = (e.clientX - r.left) / r.width - 0.5;   // -0.5 (left) … 0.5 (right)
            py = (e.clientY - r.top) / r.height - 0.5;   // -0.5 (top) … 0.5 (bottom)
            hero.classList.add('is-tilting');
            hero.style.setProperty('--tilt-zoom', '1.1');
            if (!frame) frame = requestAnimationFrame(apply);
        });
        hero.addEventListener('pointerleave', function () {
            px = py = 0;
            hero.classList.remove('is-tilting');
            hero.style.setProperty('--tilt-zoom', '1');
            apply();
        });
    })();
</script>
