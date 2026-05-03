(function () {
  'use strict';

  var body = document.body;
  var header = document.querySelector('[data-igp-theme-header]');
  var toggle = document.querySelector('[data-igp-menu-toggle]');

  if (toggle) {
    toggle.addEventListener('click', function () {
      var open = body.classList.toggle('igp-theme-menu-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  if (header && 'IntersectionObserver' in window) {
    var sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    header.parentNode.insertBefore(sentinel, header);

    var observer = new IntersectionObserver(function (entries) {
      if (!entries.length) return;
      header.classList.toggle('is-scrolled', !entries[0].isIntersecting);
    }, { threshold: 0 });

    observer.observe(sentinel);
  }
})();
