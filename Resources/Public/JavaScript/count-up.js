/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Animates numbers marked with [data-count-up] from 0 to their value once they become visible.
 * The server renders the final value, so without JavaScript (or with reduced motion) nothing is lost.
 */
const DURATION = 900;
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const numberFormat = new Intl.NumberFormat(document.documentElement.lang || undefined);

const easeOutCubic = (progress) => 1 - Math.pow(1 - progress, 3);

function animate(element) {
  const target = Number.parseInt(element.dataset.countUp, 10);
  if (Number.isNaN(target)) {
    return;
  }

  if (reducedMotion.matches || target === 0) {
    element.textContent = numberFormat.format(target);
    return;
  }

  const start = performance.now();
  const step = (now) => {
    const progress = Math.min(1, (now - start) / DURATION);
    element.textContent = numberFormat.format(Math.round(target * easeOutCubic(progress)));
    if (progress < 1) {
      requestAnimationFrame(step);
    }
  };
  requestAnimationFrame(step);
}

const observer = 'IntersectionObserver' in window
  ? new IntersectionObserver((entries) => {
    entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
      observer.unobserve(entry.target);
      animate(entry.target);
    });
  }, { threshold: 0.4 })
  : null;

/**
 * @param {ParentNode} root
 */
export function countUp(root) {
  root.querySelectorAll('[data-count-up]').forEach((element) => {
    if (observer === null) {
      animate(element);
      return;
    }
    observer.observe(element);
  });
}
