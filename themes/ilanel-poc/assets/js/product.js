/**
 * Hero carousel + configurator.
 *
 * Vanilla, no dependencies — the Playground sandbox blocks external
 * scripts and the behaviour is simple enough not to need a library.
 */
(function () {
  'use strict';

  /* --- Hero carousel: cross-fade, matching RG's .slider--fade --------- */

  function initHero() {
    var hero = document.querySelector('.js-hero');
    if (!hero) return;

    var slides = hero.querySelectorAll('.rg-hero__slide');
    if (slides.length < 2) return;

    var dots = hero.querySelectorAll('.rg-hero__dot');
    var index = 0;
    var timer = null;

    function show(next) {
      index = (next + slides.length) % slides.length;

      slides.forEach(function (slide, i) {
        slide.classList.toggle('is-active', i === index);
      });

      dots.forEach(function (dot, i) {
        dot.classList.toggle('is-active', i === index);
      });
    }

    function advance() {
      show(index + 1);
    }

    function restart() {
      window.clearInterval(timer);
      timer = window.setInterval(advance, 6000);
    }

    hero.querySelector('.js-hero-next').addEventListener('click', function () {
      show(index + 1);
      restart();
    });

    hero.querySelector('.js-hero-prev').addEventListener('click', function () {
      show(index - 1);
      restart();
    });

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        show(parseInt(dot.dataset.index, 10));
        restart();
      });
    });

    restart();
  }

  /* --- Configurator: swap preview, update summary and price ----------- */

  function initConfigurator() {
    var form = document.querySelector('.rg-config');
    if (!form) return;

    var preview = document.querySelector('.js-config-image');
    var summary = form.querySelector('.js-config-summary');
    var priceEl = form.querySelector('.js-config-price');
    var base = parseFloat(form.dataset.basePrice || '0');

    // Read the currency symbol off the server-rendered price so we don't
    // hardcode it — Woo may be configured for any currency.
    var rendered = priceEl ? priceEl.textContent.trim() : '';
    var symbol = (rendered.match(/^[^\d]*/) || [''])[0] || '$';

    function money(value) {
      return (
        symbol +
        value.toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      );
    }

    function update() {
      var length = form.querySelector('input[name="ilanel_length"]:checked');
      var finish = form.querySelector('input[name="ilanel_finish"]:checked');

      if (finish && preview && finish.dataset.image) {
        preview.src = finish.dataset.image;
      }

      var parts = [];
      if (length) parts.push(length.value);
      if (finish) parts.push(finish.value);
      if (summary) summary.textContent = parts.join(' · ');

      if (priceEl) {
        var increment = length ? parseFloat(length.dataset.increment || '0') : 0;
        priceEl.textContent = money(base + increment);
      }
    }

    form.addEventListener('change', update);
    update();
  }

  /* --- Lit / unlit toggle -------------------------------------------- */

  function initLightSwitch() {
    var btn = document.querySelector('.js-lightswitch');
    var preview = document.querySelector('.rg-configure__preview');
    if (!btn || !preview) return;

    var label = btn.querySelector('.rg-lightswitch__text');
    var lit = true;

    btn.addEventListener('click', function () {
      lit = !lit;
      preview.classList.toggle('is-unlit', !lit);
      btn.setAttribute('aria-pressed', String(lit));
      if (label) label.textContent = lit ? 'Lit' : 'Unlit';
    });
  }

  /* --- Reveal on scroll ------------------------------------------------
   * Sections fade and rise slightly as they enter the viewport. Kept
   * subtle — a few hundred ms, small travel. Respects
   * prefers-reduced-motion by simply not running.
   */

  function initReveal() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (!('IntersectionObserver' in window)) return;

    var targets = document.querySelectorAll(
      '.rg-article__col, .rg-feature, .rg-section, .rg-product-card, .rg-configure__panel, .rg-configure__preview'
    );

    targets.forEach(function (el) {
      el.classList.add('rg-reveal');
    });

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        });
      },
      { rootMargin: '0px 0px -10% 0px', threshold: 0.08 }
    );

    targets.forEach(function (el) {
      io.observe(el);
    });
  }

  /* --- Sticky enquiry bar ----------------------------------------------
   * Appears once the configurator scrolls out of view, so the call to
   * action is never more than a tap away on a long page.
   */

  function initStickyBar() {
    var config = document.getElementById('configure');
    var bar = document.querySelector('.js-stickybar');
    if (!config || !bar) return;

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          // Show only once the configurator has been scrolled past.
          var past = !entry.isIntersecting && entry.boundingClientRect.top < 0;
          bar.classList.toggle('is-visible', past);
        });
      },
      { threshold: 0 }
    );

    io.observe(config);
  }

  /* --- Header state on scroll ------------------------------------------
   * Over the hero the header is white on photography; once past it, it
   * needs to flip to dark on white.
   */

  function initHeaderState() {
    var hero = document.querySelector('.rg-hero');
    if (!hero || !document.body.classList.contains('has-hero')) return;

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          document.body.classList.toggle('is-past-hero', !entry.isIntersecting);
        });
      },
      { threshold: 0, rootMargin: '-80px 0px 0px 0px' }
    );

    io.observe(hero);
  }

  function init() {
    initHero();
    initConfigurator();
    initLightSwitch();
    initReveal();
    initStickyBar();
    initHeaderState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
