/**
 * Soft page transitions.
 *
 * Loaded on every page, not just products. Two halves:
 *
 *   1. Fade the page in on load, so arriving never flashes.
 *   2. Intercept same-origin link clicks, fade out, then navigate. The
 *      browser paints the next page during the fade, so the seam between
 *      pages disappears.
 *
 * Deliberately not a SPA router — no history rewriting, no fetch, no
 * state to get out of sync. Just a cross-fade over a real navigation, so
 * the back button, refresh and deep links all behave normally.
 *
 * Falls back to plain navigation if anything is unsupported, and no-ops
 * entirely under prefers-reduced-motion.
 */
(function () {
  'use strict';

  var DURATION = 320;

  var reduced =
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* --- Fade in ------------------------------------------------------- */

  function revealPage() {
    document.documentElement.classList.add('is-ready');
  }

  if (reduced) {
    revealPage();
  } else {
    // rAF so the initial painted frame is the faded-out state, otherwise
    // the browser can skip straight to opaque and the fade is lost.
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(revealPage);
    });
  }

  /* --- Fade out before navigating ------------------------------------ */

  if (reduced) return;

  function isInternalNavigation(link, event) {
    if (event.defaultPrevented) return false;
    if (event.button !== 0) return false;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

    if (!link || !link.href) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download')) return false;
    if (link.getAttribute('rel') === 'external') return false;

    var url;
    try {
      url = new URL(link.href, window.location.href);
    } catch (e) {
      return false;
    }

    if (url.origin !== window.location.origin) return false;

    // In-page anchors scroll; they must not trigger a page fade.
    if (url.pathname === window.location.pathname && url.hash) return false;

    // Leave non-http schemes (mailto:, tel:) alone.
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;

    return url.href;
  }

  document.addEventListener('click', function (event) {
    var link = event.target.closest && event.target.closest('a');
    if (!link) return;

    var href = isInternalNavigation(link, event);
    if (!href) return;

    event.preventDefault();

    document.documentElement.classList.add('is-leaving');

    // Navigate once the fade has played. The timeout is the guarantee —
    // transitionend can be missed if the element is removed or the tab
    // is backgrounded.
    window.setTimeout(function () {
      window.location.href = href;
    }, DURATION);
  });

  /* Restoring from bfcache shows the faded-out state, so clear it. */
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      document.documentElement.classList.remove('is-leaving');
      revealPage();
    }
  });
})();
