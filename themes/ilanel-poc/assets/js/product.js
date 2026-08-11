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

  function init() {
    initHero();
    initConfigurator();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
