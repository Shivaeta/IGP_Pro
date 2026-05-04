(function () {
  'use strict';

  function qsa(root, selector) {
    return root ? Array.prototype.slice.call(root.querySelectorAll(selector)) : [];
  }

  function qs(root, selector) {
    return root ? root.querySelector(selector) : null;
  }

  function getPanelFromTrigger(trigger) {
    return trigger.closest('[data-igp-booking-panel]') || document;
  }

  function isInlineThemeAvailability(panel) {
    return !!(panel && (panel.classList && panel.classList.contains('igp-theme-tour-layout') || panel.querySelector('.igp-theme-tour-content [data-igp-availability-dock]')));
  }

  function unlockPageIfInline(panel) {
    if (!isInlineThemeAvailability(panel)) return;
    document.documentElement.classList.remove('igp-availability-panel-open');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  function revealDock(panel) {
    var dock = qs(panel, '[data-igp-availability-dock]');
    var availability = qs(panel, '[data-igp-availability-panel]');
    if (!dock || !availability) return false;

    availability.hidden = false;
    availability.classList.add('is-visible');
    dock.classList.add('is-visible');
    dock.setAttribute('data-igp-availability-open', 'true');
    unlockPageIfInline(panel);
    return true;
  }

  function syncDock(panel) {
    var dock = qs(panel, '[data-igp-availability-dock]');
    var availability = qs(panel, '[data-igp-availability-panel]');
    if (!dock || !availability) return;

    var open = !availability.hidden || availability.classList.contains('is-visible');
    dock.classList.toggle('is-visible', open);
    dock.toggleAttribute('data-igp-availability-open', open);
    if (open) unlockPageIfInline(panel);
  }

  function closeDock(panel) {
    var dock = qs(panel, '[data-igp-availability-dock]');
    var availability = qs(panel, '[data-igp-availability-panel]');
    if (!dock || !availability) return;

    availability.hidden = true;
    availability.classList.remove('is-visible');
    dock.classList.remove('is-visible');
    dock.removeAttribute('data-igp-availability-open');
    document.documentElement.classList.remove('igp-availability-panel-open');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  function ensureDetachedControlsBelongToForm(panel) {
    var form = qs(panel, '[data-igp-booking-form]');
    if (!form || !form.id) return;
    qsa(panel, '[data-igp-availability-panel] input[name], [data-igp-availability-panel] select[name], [data-igp-availability-panel] textarea[name]').forEach(function (control) {
      if (!control.getAttribute('form')) control.setAttribute('form', form.id);
    });
  }

  function normalizeMoneyText(root) {
    qsa(root || document, '.igp-booking-panel, .igp-availability-panel, .igp-checkout-page').forEach(function (scope) {
      scope.querySelectorAll('strong, span, em, del, ins, button').forEach(function (node) {
        if (node.childNodes.length !== 1 || node.firstChild.nodeType !== 3) return;
        node.textContent = node.textContent.replace(/\\u20b9|\u20b9|u20b9/gi, '₹');
      });
    });
  }

  function guardAvailabilityButton(button) {
    // The plugin owns validation. This theme bridge only prevents the UI from
    // remaining visually hidden if the plugin has already opened the panel.
    var panel = getPanelFromTrigger(button);
    window.setTimeout(function () {
      syncDock(panel);
      normalizeMoneyText(panel);
    }, 40);
    window.setTimeout(function () {
      syncDock(panel);
      normalizeMoneyText(panel);
    }, 180);
    window.setTimeout(function () {
      unlockPageIfInline(panel);
    }, 420);
  }

  document.addEventListener('DOMContentLoaded', function () {
    normalizeMoneyText(document);
    qsa(document, '[data-igp-booking-panel]').forEach(function (panel) {
      ensureDetachedControlsBelongToForm(panel);
      syncDock(panel);
      normalizeMoneyText(panel);
    });
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-igp-check-availability]');
    if (button) {
      guardAvailabilityButton(button);
      return;
    }

    var close = event.target.closest('[data-igp-close-availability]');
    if (close) {
      closeDock(getPanelFromTrigger(close));
    }
  }, true);

  // Fallback for any external script that removes `hidden` without setting the
  // dock class, and for browsers without `:has()` support.
  document.addEventListener('change', function (event) {
    var panel = event.target.closest('[data-igp-booking-panel]');
    if (panel) syncDock(panel);
  });
}());
