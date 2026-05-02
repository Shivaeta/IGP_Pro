(function () {
  'use strict';

  document.addEventListener('input', function (event) {
    if (!event.target || !event.target.classList || !event.target.classList.contains('igp-brand-token-field')) {
      return;
    }
    event.target.dataset.changed = '1';
  });
}());
