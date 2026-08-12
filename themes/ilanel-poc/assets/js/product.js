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
  /* --- Configurator ----------------------------------------------------
   * Drives real WooCommerce variations. Every price, image and stock state
   * comes from Woo; nothing here is invented.
   *
   * Three things this does that a naive variation picker does not:
   *
   *   1. Options that cannot be reached from the current selection are
   *      disabled *before* you click them, so you never land on a dead
   *      combination and have to back out.
   *   2. The price cross-fades between values instead of snapping, and
   *      counts to the new figure — a change you can follow.
   *   3. Selection state is announced to screen readers and persisted
   *      between visits.
   */

  function initConfigurator() {
    var form = document.querySelector('.rg-config');
    if (!form) return;

    var preview = document.querySelector('.js-config-image');
    var summary = form.querySelector('.js-config-summary');
    var priceEl = form.querySelector('.js-config-price');
    var unavailableEl = form.querySelector('.js-config-unavailable');

    var variations = [];
    try {
      variations = JSON.parse(form.dataset.variations || '[]');
    } catch (e) {
      variations = [];
    }

    // Axis keys, in the order the fieldsets appear.
    var axes = Array.prototype.map.call(
      form.querySelectorAll('.rg-config__group[data-axis]'),
      function (g) {
        return g.dataset.axis;
      }
    );

    if (!axes.length) return;

    function inputsFor(axis) {
      return form.querySelectorAll('input[name="attribute_' + axis + '"]');
    }

    function selected() {
      var out = {};
      axes.forEach(function (axis) {
        var checked = form.querySelector('input[name="attribute_' + axis + '"]:checked');
        out[axis] = checked ? checked.value : null;
      });
      return out;
    }

    /* A variation matches when every axis either agrees or is unconstrained
     * ("any" — Woo leaves the attribute blank when it applies to all). */
    function matches(variation, choice, ignoreAxis) {
      return axes.every(function (axis) {
        if (axis === ignoreAxis) return true;
        var want = choice[axis];
        if (!want) return true;
        var have = variation.attributes['attribute_' + axis];
        return !have || have === want;
      });
    }

    function findVariation(choice) {
      for (var i = 0; i < variations.length; i++) {
        if (matches(variations[i], choice, null)) return variations[i];
      }
      return null;
    }

    /* --- Reachability ---------------------------------------------------
     * For each option, ask: if I picked this, would any variation exist?
     * If not, dim and disable it. This is the difference between a picker
     * that guides you and one that lets you walk into a wall.
     */
    function updateReachability(choice) {
      if (!variations.length) return;

      axes.forEach(function (axis) {
        Array.prototype.forEach.call(inputsFor(axis), function (input) {
          var hypothetical = Object.assign({}, choice);
          hypothetical[axis] = input.value;

          var reachable = variations.some(function (v) {
            return matches(v, hypothetical, null);
          });

          input.disabled = !reachable;
          var label = input.closest('label');
          if (label) label.classList.toggle('is-unavailable', !reachable);
        });
      });
    }

    /* --- Price animation ------------------------------------------------
     * Counts from the old figure to the new one over ~420ms. Digits are
     * rewritten in place, so the currency symbol and any "from" wording
     * from Woo's price_html survive untouched.
     */
    var priceAnim = null;

    function animatePrice(html, from, to) {
      if (priceAnim) window.cancelAnimationFrame(priceAnim);

      var reduced =
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      if (reduced || !from || !to || from === to) {
        priceEl.innerHTML = html;
        return;
      }

      var start = performance.now();
      var DURATION = 420;

      // Rewrite the first number in the markup, preserving everything else.
      var numberPattern = /([\d][\d,]*\.?\d*)/;

      function frame(now) {
        var t = Math.min(1, (now - start) / DURATION);
        // easeOutCubic — fast then settling, matching the page's motion.
        var eased = 1 - Math.pow(1 - t, 3);
        var value = from + (to - from) * eased;

        priceEl.innerHTML = html.replace(
          numberPattern,
          value.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        );

        if (t < 1) priceAnim = window.requestAnimationFrame(frame);
      }

      priceAnim = window.requestAnimationFrame(frame);
    }

    /* --- Preview image --------------------------------------------------
     * Cross-fades rather than swapping, and preloads before showing so the
     * panel never flashes an empty frame.
     */
    function setPreview(src) {
      if (!preview || !src || preview.src === src) return;

      var next = new Image();
      next.onload = function () {
        preview.style.opacity = '0';
        window.setTimeout(function () {
          preview.src = src;
          preview.style.opacity = '';
        }, 180);
      };
      next.src = src;
    }

    /* --- Scale drawing --------------------------------------------------- */
    var scaleFixture = document.querySelector('.js-scale-fixture');
    var scaleCaption = document.querySelector('.js-scale-caption');
    var TABLE_MM = 2400;

    function updateScale() {
      if (!scaleFixture) return;

      var sized = form.querySelector('input[data-mm]:checked');
      if (!sized) return;

      var mm = parseInt(sized.dataset.mm || '0', 10);
      if (!mm) return;

      // The table is drawn at 78% of the stage; scale the fixture to match.
      scaleFixture.style.width = (mm / TABLE_MM) * 78 + '%';
      if (scaleCaption) scaleCaption.textContent = mm + ' mm';
    }

    /* --- Persistence -----------------------------------------------------
     * People compare lighting for weeks. Losing the selection on every
     * return visit is needless drop-off.
     */
    var KEY = 'ilanel-config-' + (document.body.className.match(/postid-(\d+)/) || [, 'x'])[1];

    function save() {
      try {
        window.localStorage.setItem(KEY, JSON.stringify(selected()));
      } catch (e) {
        /* Private mode or quota. Not fatal. */
      }
    }

    function restore() {
      var saved;
      try {
        saved = JSON.parse(window.localStorage.getItem(KEY) || 'null');
      } catch (e) {
        return false;
      }
      if (!saved || !window.CSS || !window.CSS.escape) return false;

      var restored = false;
      axes.forEach(function (axis) {
        if (!saved[axis]) return;
        var input = form.querySelector(
          'input[name="attribute_' + axis + '"][value="' + window.CSS.escape(saved[axis]) + '"]'
        );
        if (input && !input.checked) {
          input.checked = true;
          restored = true;
        }
      });
      return restored;
    }

    /* --- Main update ----------------------------------------------------- */
    var lastPrice = null;

    function update() {
      var choice = selected();

      updateReachability(choice);

      var variation = findVariation(choice);

      // Human-readable selection, e.g. "1800mm · Brushed Brass · Smoke".
      var parts = axes
        .map(function (axis) {
          return choice[axis];
        })
        .filter(Boolean);
      if (summary) summary.textContent = parts.join(' · ');

      if (variation) {
        if (unavailableEl) unavailableEl.hidden = true;
        form.classList.remove('is-unavailable');

        if (priceEl && variation.price_html) {
          animatePrice(variation.price_html, lastPrice, variation.display);
          lastPrice = variation.display;
        }

        setPreview(variation.image);
      } else if (variations.length) {
        // Reachability should prevent this, but a restored selection from an
        // older catalogue can land here. Say so plainly rather than showing
        // a stale price.
        if (unavailableEl) unavailableEl.hidden = false;
        form.classList.add('is-unavailable');
      }

      updateScale();
    }

    form.addEventListener('change', function () {
      update();
      save();
    });

    if (restore()) {
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
