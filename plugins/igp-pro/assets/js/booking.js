(function () {
  'use strict';

  function qs(root, selector) {
    return root ? root.querySelector(selector) : null;
  }

  function qsa(root, selector) {
    return root ? Array.prototype.slice.call(root.querySelectorAll(selector)) : [];
  }

  function escSel(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function formatMoney(amount, currency) {
    var rounded = Math.round((Number(amount) || 0) * 100) / 100;
    var number = rounded % 1 === 0 ? String(Math.round(rounded)) : rounded.toFixed(2);
    return (currency || '₹') + number.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function formatDateLabel(value) {
    if (!value) return 'Date';
    var parts = value.split('-').map(function (part) { return parseInt(part, 10); });
    if (parts.length !== 3) return value;
    var date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
  }

  function ymd(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, '0');
    var day = String(date.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
  }

  function monthTitle(date) {
    return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  }

  function serializeForm(form) {
    var data = new FormData(form);
    if (window.igpProBooking && window.igpProBooking.nonce && !data.get('nonce')) {
      data.set('nonce', window.igpProBooking.nonce);
    }
    return data;
  }

  function setMessage(scope, message, type) {
    var messageEl = qs(scope, '[data-igp-form-message]');
    if (!messageEl) return;
    messageEl.textContent = message || '';
    messageEl.dataset.type = type || '';
  }

  function setInlineNotice(panel, message, type) {
    var notice = qs(panel, '[data-igp-traveler-notice]');
    if (!notice) return;
    notice.textContent = message || '';
    notice.dataset.type = type || '';
  }

  function getAvailabilityPanel(panel) {
    if (panel && panel.__igpAvailabilityPanel) return panel.__igpAvailabilityPanel;
    var local = qs(panel, '[data-igp-availability-panel]');
    if (local) return local;
    var form = qs(panel, '[data-igp-booking-form]');
    if (form && form.id) {
      return document.querySelector('[data-igp-availability-panel][data-igp-availability-for="' + escSel(form.id) + '"]');
    }
    return null;
  }

  function getBookingScopes(panel) {
    var scopes = [panel];
    var availability = getAvailabilityPanel(panel);
    if (availability && availability !== panel && !panel.contains(availability)) scopes.push(availability);
    return scopes;
  }

  function qsaBooking(panel, selector) {
    var nodes = [];
    getBookingScopes(panel).forEach(function (scope) {
      qsa(scope, selector).forEach(function (node) {
        if (nodes.indexOf(node) === -1) nodes.push(node);
      });
    });
    return nodes;
  }

  function qsBooking(panel, selector) {
    var scopes = getBookingScopes(panel);
    for (var i = 0; i < scopes.length; i += 1) {
      var node = qs(scopes[i], selector);
      if (node) return node;
    }
    return null;
  }

  function setLoading(form, isLoading) {
    var buttons = qsa(form, 'button[type="submit"]');
    if (form && form.id) {
      qsa(document, 'button[type="submit"][form="' + escSel(form.id) + '"]').forEach(function (button) {
        if (buttons.indexOf(button) === -1) buttons.push(button);
      });
    }
    buttons.forEach(function (button) {
      button.disabled = !!isLoading;
      button.classList.toggle('is-loading', !!isLoading);
    });
  }

  function ajaxSubmit(form) {
    var data = serializeForm(form);
    var ajaxUrl = window.igpProBooking ? window.igpProBooking.ajaxUrl : '/wp-admin/admin-ajax.php';

    setLoading(form, true);
    setMessage(form, '', '');

    return fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: data
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (payload) {
        setLoading(form, false);
        if (!payload || !payload.success) {
          var error = payload && payload.data && payload.data.message ? payload.data.message : 'Request failed.';
          setMessage(form, error, 'error');
          return payload;
        }

        var data = payload.data || {};
        setMessage(form, data.message || 'Success.', 'success');

        if (data.redirect_url) {
          window.location.href = data.redirect_url;
        }

        return payload;
      })
      .catch(function () {
        setLoading(form, false);
        setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.networkError : 'Network error. Please try again.'), 'error');
      });
  }

  function getTotalGuests(panel) {
    var totalGuests = 0;
    qsa(panel, '[data-igp-guest-row] input[type="number"]').forEach(function (input) {
      totalGuests += Math.max(0, parseInt(input.value || '0', 10) || 0);
    });
    return totalGuests;
  }

  function updateTravelerSummary(panel) {
    var totalGuests = getTotalGuests(panel);
    var summary = qs(panel, '[data-igp-traveler-summary]');
    if (!summary) return;

    if (totalGuests < 1) {
      summary.textContent = 'Guest';
      return;
    }

    summary.textContent = totalGuests + ' ' + (totalGuests === 1 ? 'guest' : 'guests');
  }

  function updateCancelNote(panel) {
    var input = qs(panel, '[data-igp-tour-date]');
    var note = qsBooking(panel, '[data-igp-cancel-note]');
    if (!input || !note) return;

    if (!input.value) {
      note.textContent = 'Select a date to see the cancellation window.';
      return;
    }

    var parts = input.value.split('-').map(function (part) { return parseInt(part, 10); });
    var date = new Date(parts[0], parts[1] - 1, parts[2]);
    date.setDate(date.getDate() - 1);
    note.textContent = 'Cancel before 9:00 AM on ' + date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) + ' for a full refund';
  }

  function syncAddonRow(row) {
    var checkbox = qs(row, '[data-igp-addon-check]');
    var input = qs(row, '[data-igp-addon-qty]');
    if (!checkbox || !input) return;

    var qty = Math.max(0, parseInt(input.value || '0', 10) || 0);
    checkbox.checked = qty > 0;
    row.classList.toggle('is-selected', qty > 0);
  }

  function updateTotal(panel) {
    var totalBox = qsBooking(panel, '.igp-booking-total');
    var currency = totalBox && totalBox.dataset ? (totalBox.dataset.currency || '₹') : '₹';
    var total = 0;
    var totalGuests = 0;

    qsa(panel, '[data-igp-guest-row] input[type="number"]').forEach(function (input) {
      var qty = Math.max(0, parseInt(input.value || '0', 10) || 0);
      var price = Number(input.dataset.price || '0') || 0;
      total += qty * price;
      totalGuests += qty;
    });

    var option = qsBooking(panel, '[data-igp-tour-option]:checked');
    if (option) {
      total += (Number(option.dataset.price || '0') || 0) * Math.max(1, totalGuests);
    }

    qsaBooking(panel, '[data-igp-addon-row]').forEach(function (row) {
      syncAddonRow(row);
      var input = qs(row, '[data-igp-addon-qty]');
      if (!input) return;
      var qty = Math.max(0, parseInt(input.value || '0', 10) || 0);
      var price = Number(input.dataset.price || '0') || 0;
      total += qty * price;
    });

    var totalEl = qsBooking(panel, '[data-igp-total]');
    if (totalEl) totalEl.textContent = formatMoney(total, currency);

    updateTravelerSummary(panel);
    updateCancelNote(panel);
  }

  function buildMonth(panel, root, monthDate, selectedValue, priceLabel) {
    var month = document.createElement('div');
    month.className = 'igp-date-month';

    var title = document.createElement('h4');
    title.textContent = monthTitle(monthDate);
    month.appendChild(title);

    var grid = document.createElement('div');
    grid.className = 'igp-date-grid';

    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function (day) {
      var cell = document.createElement('span');
      cell.className = 'igp-date-weekday';
      cell.textContent = day;
      grid.appendChild(cell);
    });

    var first = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
    var last = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0);
    var offset = (first.getDay() + 6) % 7;
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    for (var i = 0; i < offset; i += 1) {
      var empty = document.createElement('span');
      empty.className = 'igp-date-empty';
      grid.appendChild(empty);
    }

    for (var dayNumber = 1; dayNumber <= last.getDate(); dayNumber += 1) {
      var date = new Date(monthDate.getFullYear(), monthDate.getMonth(), dayNumber);
      var value = ymd(date);
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'igp-date-day';
      button.dataset.date = value;
      button.innerHTML = '<strong>' + dayNumber + '</strong><small>' + priceLabel + '</small>';
      if (date < today) {
        button.disabled = true;
        button.innerHTML = '<strong>' + dayNumber + '</strong><small>-</small>';
      }
      if (value === selectedValue) button.classList.add('is-selected');
      grid.appendChild(button);
    }

    month.appendChild(grid);
    root.appendChild(month);
  }

  function renderCalendar(panel) {
    var popover = qs(panel, '[data-igp-date-popover]');
    var calendar = qs(panel, '[data-igp-date-calendar]');
    var input = qs(panel, '[data-igp-tour-date]');
    if (!popover || !calendar || !input) return;

    var currency = popover.dataset.currency || '₹';
    var price = Number(popover.dataset.price || '0') || 0;
    var priceLabel = price > 0 ? formatMoney(price, currency) : 'Available';
    var start = new Date();
    start.setDate(1);

    calendar.innerHTML = '';
    buildMonth(panel, calendar, start, input.value, priceLabel);
    buildMonth(panel, calendar, new Date(start.getFullYear(), start.getMonth() + 1, 1), input.value, priceLabel);
  }

  function initDatePicker(panel) {
    var toggle = qs(panel, '[data-igp-date-toggle]');
    var popover = qs(panel, '[data-igp-date-popover]');
    var input = qs(panel, '[data-igp-tour-date]');
    var label = qs(panel, '[data-igp-date-label]');
    if (!toggle || !popover || !input) return;

    renderCalendar(panel);

    toggle.addEventListener('click', function () {
      var open = popover.hidden;
      renderCalendar(panel);
      popover.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    popover.addEventListener('click', function (event) {
      var day = event.target.closest('[data-date]');
      if (!day || day.disabled) return;
      input.value = day.dataset.date;
      if (label) label.textContent = formatDateLabel(input.value);
      input.dispatchEvent(new Event('change', { bubbles: true }));
      popover.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
      renderCalendar(panel);
    });

    document.addEventListener('click', function (event) {
      if (popover.hidden) return;
      if (popover.contains(event.target) || toggle.contains(event.target)) return;
      popover.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    });
  }

  function initTravelerPicker(panel) {
    var toggle = qs(panel, '[data-igp-traveler-toggle]');
    var pickerPanel = qs(panel, '[data-igp-traveler-panel]');
    var apply = qs(panel, '[data-igp-traveler-apply]');

    if (toggle && pickerPanel) {
      toggle.addEventListener('click', function () {
        var open = pickerPanel.hidden;
        pickerPanel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    if (apply && pickerPanel && toggle) {
      apply.addEventListener('click', function () {
        if (getTotalGuests(panel) < 1) {
          setInlineNotice(panel, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseGuests : 'Please select at least one traveller.'), 'error');
          return;
        }
        setInlineNotice(panel, '', '');
        pickerPanel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        updateTotal(panel);
      });
    }

    document.addEventListener('click', function (event) {
      if (!pickerPanel || pickerPanel.hidden) return;
      if (pickerPanel.contains(event.target) || (toggle && toggle.contains(event.target))) return;
      pickerPanel.hidden = true;
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    });
  }

  function bindQuantityScope(scope, panel) {
    if (!scope || scope.__igpQuantityBound) return;
    scope.__igpQuantityBound = true;

    scope.addEventListener('click', function (event) {
      var plus = event.target.closest('[data-igp-qty-plus]');
      var minus = event.target.closest('[data-igp-qty-minus]');
      if (!plus && !minus) return;

      var control = event.target.closest('[data-igp-quantity]');
      var input = qs(control, 'input[type="number"]');
      if (!input) return;

      if (input.hasAttribute('data-igp-addon-qty') && getTotalGuests(panel) < 1) {
        var form = qs(panel, '[data-igp-booking-form]') || panel;
        setMessage(form, 'Please select at least one guest before adding extra services.', 'error');
        var toggle = qs(panel, '[data-igp-traveler-toggle]');
        if (toggle) toggle.click();
        return;
      }

      var value = parseInt(input.value || '0', 10) || 0;
      if (plus) value += 1;
      if (minus) value = Math.max(0, value - 1);
      input.value = String(value);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    scope.addEventListener('change', function (event) {
      if (!event.target.matches('input, select')) return;

      if (event.target.matches('[data-igp-addon-check]')) {
        var row = event.target.closest('[data-igp-addon-row]');
        var addonQty = qs(row, '[data-igp-addon-qty]');
        if (event.target.checked && getTotalGuests(panel) < 1) {
          event.target.checked = false;
          setMessage(qs(panel, '[data-igp-booking-form]') || panel, 'Please select at least one guest before adding extra services.', 'error');
          return;
        }
        if (addonQty) addonQty.value = event.target.checked ? '1' : '0';
      }
      updateTotal(panel);
    });

    scope.addEventListener('input', function (event) {
      if (event.target.matches('input[type="number"]')) updateTotal(panel);
    });
  }

  function initQuantities(panel) {
    getBookingScopes(panel).forEach(function (scope) { bindQuantityScope(scope, panel); });
    updateTotal(panel);
  }

  function findOverviewHeading(content) {
    var headings = qsa(content, 'h1, h2, h3, h4');
    for (var i = 0; i < headings.length; i += 1) {
      if ((headings[i].textContent || '').trim().toLowerCase() === 'overview') return headings[i];
    }
    return null;
  }

  function prepareAvailabilityDock(panel) {
    var availability = qs(panel, '[data-igp-availability-panel]');
    if (!availability || availability.__igpDocked) {
      if (availability) panel.__igpAvailabilityPanel = availability;
      return;
    }

    var content = panel.closest('.igp-theme-tour-content') || document.querySelector('.igp-theme-tour-content') || panel.parentElement;
    if (!content) {
      panel.__igpAvailabilityPanel = availability;
      return;
    }

    var dock = document.createElement('div');
    dock.className = 'igp-availability-dock';
    dock.dataset.igpAvailabilityDock = '';

    var galleries = qsa(content, '.wp-block-gallery, .igp-pro-gallery, .igp-gallery, .igp-tour-gallery, .gallery, figure.gallery');
    var anchor = galleries.length ? galleries[galleries.length - 1] : null;
    var overview = findOverviewHeading(content);

    if (anchor && anchor.parentNode) {
      anchor.parentNode.insertBefore(dock, anchor.nextSibling);
    } else if (overview && overview.parentNode) {
      overview.parentNode.insertBefore(dock, overview);
    } else if (panel.parentNode) {
      panel.parentNode.insertBefore(dock, panel);
    } else {
      content.appendChild(dock);
    }

    dock.appendChild(availability);
    availability.__igpDocked = true;
    panel.__igpAvailabilityPanel = availability;
    bindQuantityScope(availability, panel);
  }

  function openAvailability(panel) {
    var form = qs(panel, '[data-igp-booking-form]');
    var date = qs(panel, '[data-igp-tour-date]');
    if (date && !date.value) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseDate : 'Please select a tour date.'), 'error');
      var dateToggle = qs(panel, '[data-igp-date-toggle]');
      if (dateToggle) dateToggle.click();
      return false;
    }

    if (getTotalGuests(panel) < 1) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseGuests : 'Please select at least one traveller.'), 'error');
      var guestToggle = qs(panel, '[data-igp-traveler-toggle]');
      if (guestToggle) guestToggle.click();
      return false;
    }

    setMessage(form, '', '');
    prepareAvailabilityDock(panel);
    var details = getAvailabilityPanel(panel);
    if (details) {
      details.hidden = false;
      details.classList.add('is-visible');
      if (details.parentElement) details.parentElement.classList.add('is-visible');
      details.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    updateTotal(panel);
    return true;
  }

  function validateBookingForm(form, panel) {
    if (!openAvailability(panel)) return false;

    if (!form.checkValidity()) {
      form.reportValidity();
      return false;
    }

    if (getTotalGuests(panel) < 1) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseGuests : 'Please select at least one traveller.'), 'error');
      return false;
    }

    return true;
  }

  function openEnquiry(panel) {
    var modal = qs(panel, '[data-igp-enquiry-modal]');
    if (!modal) return;
    modal.hidden = false;
    document.documentElement.classList.add('igp-enquiry-modal-open');
    var first = qs(modal, 'input, textarea, button');
    if (first) first.focus();
  }

  function closeEnquiry(panel) {
    var modal = qs(panel, '[data-igp-enquiry-modal]');
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.classList.remove('igp-enquiry-modal-open');
  }

  function initForms(panel) {
    var availabilityButton = qs(panel, '[data-igp-check-availability]');
    if (availabilityButton) {
      availabilityButton.addEventListener('click', function () { openAvailability(panel); });
    }

    qsa(panel, '[data-igp-open-enquiry]').forEach(function (button) {
      button.addEventListener('click', function () { openEnquiry(panel); });
    });

    qsa(panel, '[data-igp-close-enquiry]').forEach(function (button) {
      button.addEventListener('click', function () { closeEnquiry(panel); });
    });

    panel.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeEnquiry(panel);
    });

    var bookingForm = qs(panel, '[data-igp-booking-form]');
    if (bookingForm) {
      bookingForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!validateBookingForm(bookingForm, panel)) return;
        ajaxSubmit(bookingForm);
      });
    }

    var enquiryForm = qs(panel, '[data-igp-enquiry-form]');
    if (enquiryForm) {
      enquiryForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!enquiryForm.checkValidity()) {
          enquiryForm.reportValidity();
          return;
        }
        ajaxSubmit(enquiryForm).then(function (payload) {
          if (payload && payload.success) {
            setTimeout(function () { closeEnquiry(panel); }, 900);
          }
        });
      });
    }
  }

  function movePanelIntoThemeSidebar(panel) {
    var sidebar = document.querySelector('.igp-theme-booking-card');
    if (!sidebar || sidebar.contains(panel)) return;
    sidebar.innerHTML = '';
    sidebar.appendChild(panel);
    sidebar.classList.add('igp-theme-booking-card--igp-powered');
  }

  function initPanel(panel) {
    initDatePicker(panel);
    initTravelerPicker(panel);
    prepareAvailabilityDock(panel);
    initQuantities(panel);
    initForms(panel);
  }

  document.addEventListener('DOMContentLoaded', function () {
    qsa(document, '[data-igp-booking-panel]').forEach(function (panel) {
      initPanel(panel);
      movePanelIntoThemeSidebar(panel);
    });

    qsa(document, '[data-igp-checkout-form]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }
        ajaxSubmit(form);
      });
    });
  });
}());
