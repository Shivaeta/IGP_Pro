(function () {
  'use strict';

  function qs(root, selector) {
    return root ? root.querySelector(selector) : null;
  }

  function qsa(root, selector) {
    return root ? Array.prototype.slice.call(root.querySelectorAll(selector)) : [];
  }

  function escSel(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function formatMoney(amount, currency) {
    var rounded = Math.round((Number(amount) || 0) * 100) / 100;
    var number = rounded % 1 === 0 ? String(Math.round(rounded)) : rounded.toFixed(2);
    return (currency || '₹') + number.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function ymd(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
  }

  function parseYmd(value) {
    var parts = String(value || '').split('-').map(function (part) { return parseInt(part, 10); });
    if (parts.length !== 3 || !parts[0] || !parts[1] || !parts[2]) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }

  function formatDateLabel(value) {
    var date = parseYmd(value);
    if (!date) return 'Date';
    return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
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
    notice.classList.toggle('is-visible', !!message);
  }

  function setLoading(form, isLoading) {
    qsa(form, 'button[type="submit"], button[data-igp-check-availability]').forEach(function (button) {
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
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        setLoading(form, false);
        if (!payload || !payload.success) {
          var error = payload && payload.data && payload.data.message ? payload.data.message : 'Request failed.';
          setMessage(form, error, 'error');
          return payload;
        }

        var data = payload.data || {};
        setMessage(form, data.message || 'Success.', 'success');
        if (data.redirect_url) window.location.href = data.redirect_url;
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

  function updateGuestButtons(scope) {
    qsa(scope, '[data-igp-quantity]').forEach(function (control) {
      var input = qs(control, 'input[type="number"]');
      var minus = qs(control, '[data-igp-qty-minus]');
      if (!input || !minus) return;
      var value = Math.max(0, parseInt(input.value || '0', 10) || 0);
      minus.classList.toggle('disabled', value <= 0);
      minus.disabled = value <= 0;
    });
  }

  function updateTravelerSummary(panel) {
    var totalGuests = getTotalGuests(panel);
    var summary = qs(panel, '[data-igp-traveler-summary]');
    if (!summary) return;
    summary.textContent = totalGuests < 1 ? 'Guest' : totalGuests + ' ' + (totalGuests === 1 ? 'guest' : 'guests');
  }

  function getCurrency(panel) {
    var totalBox = qs(panel, '.igp-booking-total');
    if (totalBox && totalBox.dataset && totalBox.dataset.currency) return totalBox.dataset.currency;
    var popover = qs(panel, '[data-igp-date-popover]');
    return popover && popover.dataset ? (popover.dataset.currency || '₹') : '₹';
  }

  function updateCancelNote(panel) {
    var input = qs(panel, '[data-igp-tour-date]');
    var note = qs(panel, '[data-igp-cancel-note]');
    if (!input || !note) return;
    var date = parseYmd(input.value);
    if (!date) {
      note.textContent = 'Select a date to see the cancellation window.';
      return;
    }
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
    var currency = getCurrency(panel);
    var total = 0;
    var totalGuests = 0;

    qsa(panel, '[data-igp-guest-row] input[type="number"]').forEach(function (input) {
      var qty = Math.max(0, parseInt(input.value || '0', 10) || 0);
      var price = Number(input.dataset.price || '0') || 0;
      total += qty * price;
      totalGuests += qty;
    });

    var option = qs(panel, '[data-igp-tour-option]:checked');
    if (option) total += (Number(option.dataset.price || '0') || 0) * Math.max(1, totalGuests);

    qsa(panel, '[data-igp-addon-row]').forEach(function (row) {
      syncAddonRow(row);
      var input = qs(row, '[data-igp-addon-qty]');
      if (!input) return;
      total += Math.max(0, parseInt(input.value || '0', 10) || 0) * (Number(input.dataset.price || '0') || 0);
    });

    var totalEl = qs(panel, '[data-igp-total]');
    if (totalEl) totalEl.textContent = formatMoney(total, currency);
    updateTravelerSummary(panel);
    updateGuestButtons(panel);
    updateCancelNote(panel);
  }

  function getCalendarRules(popover) {
    try {
      var parsed = JSON.parse(popover.getAttribute('data-dates') || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function isAllowedDate(date, rules) {
    if (!rules.length) return true;
    var value = ymd(date);
    var dayName = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][date.getDay()];
    return rules.some(function (rule) {
      var start = rule.start_date || '';
      var end = rule.end_date || 'no_end_date';
      var days = Array.isArray(rule.trip_days) ? rule.trip_days : [];
      if (start && value < start) return false;
      if (end && end !== 'no_end_date' && value > end) return false;
      if (days.length && days.indexOf(dayName) === -1) return false;
      return true;
    });
  }

  function priceForDate(popover, date) {
    var rules = getCalendarRules(popover);
    var match = rules.filter(function (rule) {
      var value = ymd(date);
      var start = rule.start_date || '';
      var end = rule.end_date || 'no_end_date';
      if (start && value < start) return false;
      if (end && end !== 'no_end_date' && value > end) return false;
      return true;
    })[0];
    if (match && match.format_price) return match.format_price;
    var price = Number(popover.dataset.price || '0') || 0;
    return price > 0 ? formatMoney(price, popover.dataset.currency || '₹') : 'Available';
  }

  function renderCalendar(panel, monthDate) {
    var popover = qs(panel, '[data-igp-date-popover]');
    var dates = qs(panel, '[data-igp-date-calendar]');
    var title = qs(panel, '[data-igp-calendar-title]');
    var input = qs(panel, '[data-igp-tour-date]');
    if (!popover || !dates || !input) return;

    var date = monthDate || panel.__igpCalendarMonth || new Date();
    date = new Date(date.getFullYear(), date.getMonth(), 1);
    panel.__igpCalendarMonth = date;

    if (title) title.textContent = date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    dates.innerHTML = '';

    var first = new Date(date.getFullYear(), date.getMonth(), 1);
    var last = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    var offset = (first.getDay() + 6) % 7;
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var rules = getCalendarRules(popover);

    for (var i = 0; i < offset; i += 1) dates.appendChild(document.createElement('div'));

    for (var day = 1; day <= last.getDate(); day += 1) {
      var current = new Date(date.getFullYear(), date.getMonth(), day);
      var value = ymd(current);
      var item = document.createElement('button');
      item.type = 'button';
      item.className = 'date';
      item.dataset.date = value;
      item.innerHTML = '<span>' + day + '</span><div class="price">' + priceForDate(popover, current) + '</div>';
      if (current < today || !isAllowedDate(current, rules)) {
        item.classList.add('disabled');
        item.disabled = true;
        if (!isAllowedDate(current, rules)) item.querySelector('.price').textContent = '-';
      }
      if (value === input.value) item.classList.add('selected');
      if (value === ymd(today)) item.classList.add('today');
      dates.appendChild(item);
    }
  }

  function initDatePicker(panel) {
    var toggle = qs(panel, '[data-igp-date-toggle]');
    var popover = qs(panel, '[data-igp-date-popover]');
    var input = qs(panel, '[data-igp-tour-date]');
    var bookingDate = qs(panel, 'input[name="booking_date"]');
    var label = qs(panel, '[data-igp-date-label]');
    if (!toggle || !popover || !input) return;

    renderCalendar(panel);

    function close() {
      popover.hidden = true;
      popover.classList.remove('is-active');
      toggle.setAttribute('aria-expanded', 'false');
    }

    function open() {
      renderCalendar(panel);
      popover.hidden = false;
      popover.classList.add('is-active');
      toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      popover.hidden ? open() : close();
    });

    popover.addEventListener('click', function (event) {
      var prev = event.target.closest('[data-igp-calendar-prev]');
      var next = event.target.closest('[data-igp-calendar-next]');
      var apply = event.target.closest('[data-igp-calendar-apply]');
      var closer = event.target.closest('[data-igp-calendar-close]');
      var day = event.target.closest('[data-date]');

      if (prev) {
        event.preventDefault();
        var p = panel.__igpCalendarMonth || new Date();
        renderCalendar(panel, new Date(p.getFullYear(), p.getMonth() - 1, 1));
        return;
      }
      if (next) {
        event.preventDefault();
        var n = panel.__igpCalendarMonth || new Date();
        renderCalendar(panel, new Date(n.getFullYear(), n.getMonth() + 1, 1));
        return;
      }
      if (apply || closer) {
        event.preventDefault();
        close();
        return;
      }
      if (day && !day.disabled) {
        event.preventDefault();
        input.value = day.dataset.date;
        if (bookingDate) bookingDate.value = day.dataset.date;
        if (label) label.textContent = formatDateLabel(input.value);
        input.dispatchEvent(new Event('change', { bubbles: true }));
        renderCalendar(panel);
        close();
      }
    });

    document.addEventListener('click', function (event) {
      if (popover.hidden) return;
      if (popover.contains(event.target) || toggle.contains(event.target)) return;
      close();
    });
  }

  function initTravelerPicker(panel) {
    var toggle = qs(panel, '[data-igp-traveler-toggle]');
    var pickerPanel = qs(panel, '[data-igp-traveler-panel]');
    var apply = qs(panel, '[data-igp-traveler-apply]');
    if (!toggle || !pickerPanel) return;

    function close() {
      pickerPanel.hidden = true;
      pickerPanel.classList.remove('is-active');
      toggle.setAttribute('aria-expanded', 'false');
    }
    function open() {
      pickerPanel.hidden = false;
      pickerPanel.classList.add('is-active');
      toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      pickerPanel.hidden ? open() : close();
    });

    if (apply) {
      apply.addEventListener('click', function (event) {
        event.preventDefault();
        if (getTotalGuests(panel) < 1) {
          setInlineNotice(panel, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseGuests : 'Please select guests'), 'error');
          return;
        }
        setInlineNotice(panel, '', '');
        close();
        updateTotal(panel);
      });
    }

    document.addEventListener('click', function (event) {
      if (pickerPanel.hidden) return;
      if (pickerPanel.contains(event.target) || toggle.contains(event.target)) return;
      close();
    });
  }

  function bindQuantities(panel) {
    panel.addEventListener('click', function (event) {
      var plus = event.target.closest('[data-igp-qty-plus]');
      var minus = event.target.closest('[data-igp-qty-minus]');
      if (!plus && !minus) return;
      event.preventDefault();

      var control = event.target.closest('[data-igp-quantity]');
      var input = qs(control, 'input[type="number"]');
      if (!input) return;
      var max = input.max ? parseInt(input.max, 10) : 9999;
      var value = Math.max(0, parseInt(input.value || '0', 10) || 0);
      if (plus) value = Math.min(max, value + 1);
      if (minus) value = Math.max(0, value - 1);
      input.value = String(value);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    panel.addEventListener('change', function (event) {
      if (!event.target.matches('input, select')) return;
      if (event.target.matches('[data-igp-addon-check]')) {
        var row = event.target.closest('[data-igp-addon-row]');
        var addonQty = qs(row, '[data-igp-addon-qty]');
        if (addonQty) addonQty.value = event.target.checked ? '1' : '0';
      }
      updateTotal(panel);
    });

    panel.addEventListener('input', function (event) {
      if (event.target.matches('input[type="number"]')) updateTotal(panel);
    });

    updateTotal(panel);
  }

  function revealAvailability(panel) {
    var form = qs(panel, '[data-igp-booking-form]');
    var date = qs(panel, '[data-igp-tour-date]');
    if (date && !date.value) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseDate : 'Please select a tour date.'), 'error');
      var dateToggle = qs(panel, '[data-igp-date-toggle]');
      if (dateToggle) dateToggle.click();
      return false;
    }
    if (getTotalGuests(panel) < 1) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseGuests : 'Please select guests'), 'error');
      var guestToggle = qs(panel, '[data-igp-traveler-toggle]');
      if (guestToggle) guestToggle.click();
      return false;
    }

    setMessage(form, '', '');
    var availability = qs(panel, '[data-igp-availability-panel]');
    if (availability) {
      availability.hidden = false;
      availability.classList.add('is-visible');
      document.documentElement.classList.add('igp-availability-panel-open');
      var first = qs(availability, 'input, button, select, textarea, a');
      if (first) first.focus({ preventScroll: true });
    }
    updateTotal(panel);
    return true;
  }

  function closeAvailability(panel) {
    var availability = qs(panel, '[data-igp-availability-panel]');
    if (!availability) return;
    availability.hidden = true;
    availability.classList.remove('is-visible');
    document.documentElement.classList.remove('igp-availability-panel-open');
    var trigger = qs(panel, '[data-igp-check-availability]');
    if (trigger) trigger.focus({ preventScroll: true });
  }

  function openEnquiry(panel) {
    var modal = qs(panel, '[data-igp-enquiry-modal]');
    if (!modal) return;
    modal.hidden = false;
    modal.classList.add('is-active');
    document.documentElement.classList.add('igp-enquiry-modal-open');
    var first = qs(modal, 'input, textarea, button');
    if (first) first.focus();
  }

  function closeEnquiry(panel) {
    var modal = qs(panel, '[data-igp-enquiry-modal]');
    if (!modal) return;
    modal.hidden = true;
    modal.classList.remove('is-active');
    document.documentElement.classList.remove('igp-enquiry-modal-open');
  }

  function initFloatingLabels(panel) {
    qsa(panel, '.igp-enquiry-form input, .igp-enquiry-form textarea').forEach(function (field) {
      function sync() {
        var group = field.closest('.igp-enquiry-field');
        if (group) group.classList.toggle('focus', !!field.value || document.activeElement === field);
      }
      field.addEventListener('focus', sync);
      field.addEventListener('blur', sync);
      field.addEventListener('input', sync);
      sync();
    });
  }

  function initForms(panel) {
    var availabilityButton = qs(panel, '[data-igp-check-availability]');
    if (availabilityButton) availabilityButton.addEventListener('click', function () { revealAvailability(panel); });

    qsa(panel, '[data-igp-close-availability]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        closeAvailability(panel);
      });
    });

    qsa(panel, '[data-igp-open-enquiry]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        openEnquiry(panel);
      });
    });

    qsa(panel, '[data-igp-close-enquiry]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        closeEnquiry(panel);
      });
    });

    panel.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeAvailability(panel);
        closeEnquiry(panel);
      }
    });

    var bookingForm = qs(panel, '[data-igp-booking-form]');
    if (bookingForm) {
      bookingForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!revealAvailability(panel)) return;
        if (!bookingForm.checkValidity()) {
          bookingForm.reportValidity();
          return;
        }
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
          if (payload && payload.success) setTimeout(function () { closeEnquiry(panel); }, 900);
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
    bindQuantities(panel);
    initForms(panel);
    initFloatingLabels(panel);
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
