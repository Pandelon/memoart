/**
 * @file
 * Testimonials slider: nyilak, pöttyök és autoplay a scroll-snap track-hez.
 */
(function (Drupal, once) {
  Drupal.behaviors.moniTestimonials = {
    attach(context) {
      once('testimonials', '.testimonials', context).forEach((el) => {
        const track = el.querySelector('.testimonials__track');
        const slides = [...track.children];
        const dots = [...el.querySelectorAll('.testimonials__dots button')];
        const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
        let current = 0;

        if (slides.length < 2) {
          return;
        }

        const go = (index) => {
          current = (index + slides.length) % slides.length;
          track.scrollTo({
            left: current * track.clientWidth,
            behavior: reduceMotion ? 'auto' : 'smooth',
          });
        };

        // Az aktív pötty a ténylegesen látható slide-ot követi (swipe-nál is).
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              current = slides.indexOf(entry.target);
              dots.forEach((dot, n) => dot.setAttribute('aria-current', n === current));
            }
          });
        }, { root: track, threshold: 0.6 });
        slides.forEach((slide) => observer.observe(slide));

        el.querySelector('.testimonials__prev').addEventListener('click', () => go(current - 1));
        el.querySelector('.testimonials__next').addEventListener('click', () => go(current + 1));
        dots.forEach((dot, n) => dot.addEventListener('click', () => go(n)));

        const delay = Number(el.dataset.autoplay);
        if (delay && !reduceMotion) {
          let paused = false;
          el.addEventListener('mouseenter', () => { paused = true; });
          el.addEventListener('mouseleave', () => { paused = false; });
          el.addEventListener('focusin', () => { paused = true; });
          el.addEventListener('focusout', () => { paused = false; });
          setInterval(() => {
            if (!paused && !document.hidden) {
              go(current + 1);
            }
          }, delay);
        }
      });
    },
  };
})(Drupal, once);
