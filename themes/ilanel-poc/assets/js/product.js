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

    /* Keyboard: arrows step the carousel when it is in view. */
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      var box = hero.getBoundingClientRect();
      if (box.bottom < 0 || box.top > window.innerHeight) return;
      show(index + (e.key === 'ArrowRight' ? 1 : -1));
      restart();
    });

    /* Swipe on touch devices. */
    var startX = null;

    hero.addEventListener(
      'touchstart',
      function (e) {
        startX = e.touches[0].clientX;
      },
      { passive: true }
    );

    hero.addEventListener(
      'touchend',
      function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 45) {
          show(index + (dx < 0 ? 1 : -1));
          restart();
        }
        startX = null;
      },
      { passive: true }
    );

    /* Pause the auto-advance while the pointer rests on the hero — nothing
     * is more irritating than an image changing while you study it. */
    hero.addEventListener('mouseenter', function () {
      window.clearInterval(timer);
    });

    hero.addEventListener('mouseleave', restart);

    restart();
  }

  /* --- Lightbox --------------------------------------------------------
   * Click a story image or the configurator preview to inspect it full
   * screen. These are hand-finished pieces; buyers want to see the join,
   * the glass, the finish up close.
   *
   * The hero is deliberately excluded — it is a carousel, and a zoom
   * cursor over it competes with the slide controls.
   */

  function initLightbox() {
    var storyImgs = document.querySelectorAll('.rg-feature img, .rg-configure__preview img');

    if (!storyImgs.length) return;

    var box = document.createElement('div');
    box.className = 'rg-lightbox';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Image viewer');
    box.innerHTML =
      '<button class="rg-lightbox__close" type="button" aria-label="Close">&times;</button>' +
      '<img class="rg-lightbox__img" alt="">';
    document.body.appendChild(box);

    var img = box.querySelector('.rg-lightbox__img');
    var lastFocus = null;

    function open(src) {
      img.src = src;
      box.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      lastFocus = document.activeElement;
      box.querySelector('.rg-lightbox__close').focus();
    }

    function close() {
      box.classList.remove('is-open');
      document.body.style.overflow = '';
      if (lastFocus) lastFocus.focus();
    }

    box.addEventListener('click', function (e) {
      if (e.target === box || e.target.closest('.rg-lightbox__close')) close();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && box.classList.contains('is-open')) close();
    });

    storyImgs.forEach(function (el) {
      el.style.cursor = 'zoom-in';
      el.addEventListener('click', function () {
        open(el.currentSrc || el.src);
      });
    });
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

    /* Scale drawing: draw the chosen length against a 2400mm table. */
    var scaleFixture = document.querySelector('.js-scale-fixture');
    var scaleCaption = document.querySelector('.js-scale-caption');
    var TABLE_MM = 2400;

    function updateScale(lengthInput) {
      if (!scaleFixture || !lengthInput) return;

      var mm = parseInt(lengthInput.dataset.mm || '0', 10);
      if (!mm) return;

      // The table is drawn at 78% of the stage; scale the fixture to match.
      var pct = (mm / TABLE_MM) * 78;
      scaleFixture.style.width = pct + '%';

      if (scaleCaption) scaleCaption.textContent = mm + ' mm';
    }

    /* Remember the configuration between visits.
     *
     * People shopping for lighting compare for weeks. Losing their
     * selection on every return visit is a needless drop-off. */
    var KEY = 'ilanel-config-' + (document.body.className.match(/postid-(\d+)/) || [, 'x'])[1];

    function save() {
      try {
        var length = form.querySelector('input[name="ilanel_length"]:checked');
        var finish = form.querySelector('input[name="ilanel_finish"]:checked');
        window.localStorage.setItem(
          KEY,
          JSON.stringify({
            length: length ? length.value : null,
            finish: finish ? finish.value : null,
          })
        );
      } catch (e) {
        /* Storage can be unavailable (private mode, quota). Not fatal. */
      }
    }

    function restore() {
      var saved;
      try {
        saved = JSON.parse(window.localStorage.getItem(KEY) || 'null');
      } catch (e) {
        return false;
      }
      if (!saved) return false;

      var restored = false;

      ['length', 'finish'].forEach(function (field) {
        if (!saved[field]) return;
        var input = form.querySelector(
          'input[name="ilanel_' + field + '"][value="' + window.CSS.escape(saved[field]) + '"]'
        );
        if (input && !input.checked) {
          input.checked = true;
          restored = true;
        }
      });

      return restored;
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

      updateScale(length);
    }

    form.addEventListener('change', function () {
      update();
      save();
    });

    if (window.CSS && window.CSS.escape && restore()) {
      var note = form.querySelector('.js-config-restored');
      if (note) note.hidden = false;
    }

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
    initLightbox();
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
