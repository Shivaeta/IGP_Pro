(function () {
  function initTabs(scope) {
    var roots = (scope || document).querySelectorAll('.igp-pro-tabs');
    roots.forEach(function (root) {
      var tabs = Array.prototype.slice.call(root.querySelectorAll('.igp-pro-tabs__tab'));
      var panels = Array.prototype.slice.call(root.querySelectorAll('.igp-pro-tabs__panel'));
      if (!tabs.length || !panels.length) return;
      function activate(hash) {
        tabs.forEach(function (tab, index) {
          var active = tab.getAttribute('href') === hash || (!hash && index === 0);
          tab.classList.toggle('is-active', active);
          tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (panel, index) {
          var active = ('#' + panel.id) === hash || (!hash && index === 0);
          panel.classList.toggle('is-active', active);
          panel.hidden = !active;
        });
      }
      tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
          event.preventDefault();
          activate(tab.getAttribute('href'));
        });
      });
      activate(tabs[0] ? tabs[0].getAttribute('href') : null);
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initTabs(document); });
  } else {
    initTabs(document);
  }
})();
