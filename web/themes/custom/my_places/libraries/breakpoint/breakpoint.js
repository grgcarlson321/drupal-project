/**
 * @file
 * Breakpoint utility for the My Places theme.
 *
 * Exposes a `bPoints` global that mirrors how the GSAP-powered map-display
 * component checks named breakpoints. Token values must match the SCSS
 * $breakpoints map in partials/_mixins.scss.
 *
 * Usage in JS:
 *   if (bPoints.matches('large')) { ... }
 */
(function (Drupal, window) {
  const breakpoints = {
    tiny:   480,
    small:  640,
    medium: 768,
    large:  1024,
    xlarge: 1280,
  };

  window.bPoints = {
    /**
     * Returns true if the viewport is at or above the named breakpoint.
     *
     * @param {string} name - One of: tiny, small, medium, large, xlarge.
     * @return {boolean}
     */
    matches(name) {
      const minWidth = breakpoints[name];
      if (!minWidth) return false;
      return window.matchMedia('(min-width: ' + minWidth + 'px)').matches;
    },
  };
})(Drupal, window);
