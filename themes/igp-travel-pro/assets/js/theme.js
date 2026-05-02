(function () {
  var toggle = document.querySelector('[data-igp-menu-toggle]');
  if (!toggle) return;
  toggle.addEventListener('click', function () {
    var open = document.body.classList.toggle('igp-theme-menu-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
