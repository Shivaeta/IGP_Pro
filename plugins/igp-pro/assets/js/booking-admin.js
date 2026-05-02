(function () {
  'use strict';

  function qs(root, selector) {
    return root ? root.querySelector(selector) : null;
  }

  function rowHtml(kind) {
    var prefix = 'igp_booking_' + kind;
    var extra = '';
    if (kind === 'addons') {
      extra = '<textarea name="' + prefix + '[included][]" placeholder="Included items, one per line"></textarea>' +
        '<textarea name="' + prefix + '[excluded][]" placeholder="Excluded items, one per line"></textarea>';
    }

    return '' +
      '<div class="igp-booking-repeater__row" data-igp-row>' +
      '<input type="text" name="' + prefix + '[id][]" value="" placeholder="id">' +
      '<input type="text" name="' + prefix + '[label][]" value="" placeholder="Label">' +
      '<input type="number" step="0.01" min="0" name="' + prefix + '[price][]" value="0" placeholder="Price">' +
      '<input type="text" name="' + prefix + '[description][]" value="" placeholder="Description">' +
      extra +
      '<button type="button" class="button-link-delete" data-igp-remove-row>Remove</button>' +
      '</div>';
  }

  function uniqueIdFromLabel(label) {
    return String(label || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 60);
  }

  function initRepeater(repeater) {
    var kind = repeater.dataset.igpBookingRepeater;
    var rows = qs(repeater, '[data-igp-rows]');
    var add = qs(repeater, '[data-igp-add-row]');

    if (add && rows) {
      add.addEventListener('click', function () {
        rows.insertAdjacentHTML('beforeend', rowHtml(kind));
        var last = rows.lastElementChild;
        var input = qs(last, 'input[name$="[label][]"]');
        if (input) input.focus();
      });
    }

    repeater.addEventListener('click', function (event) {
      var remove = event.target.closest('[data-igp-remove-row]');
      if (!remove) return;
      var row = remove.closest('[data-igp-row]');
      if (row) row.remove();
    });

    repeater.addEventListener('input', function (event) {
      var input = event.target;
      if (!input.matches('input[name$="[label][]"]')) return;
      var row = input.closest('[data-igp-row]');
      var id = qs(row, 'input[name$="[id][]"]');
      if (!id || id.value) return;
      id.value = uniqueIdFromLabel(input.value);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.slice.call(document.querySelectorAll('[data-igp-booking-repeater]')).forEach(initRepeater);
  });
}());
