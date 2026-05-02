(function () {
  'use strict';

  function qs(root, selector) {
    return root ? root.querySelector(selector) : null;
  }

  function qsa(root, selector) {
    return root ? Array.prototype.slice.call(root.querySelectorAll(selector)) : [];
  }

  function formatMoney(amount, currency) {
    var rounded = Math.round((Number(amount) || 0) * 100) / 100;
    var number = rounded % 1 === 0 ? String(Math.round(rounded)) : rounded.toFixed(2);
    return (currency || '₹') + number.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function serializeForm(form) {
    var data = new FormData(form);
    if (window.igpProBooking && window.igpProBooking.nonce && !data.get('nonce')) {
      data.set('nonce', window.igpProBooking.nonce);
    }
    return data;
  }

  function setMessage(form, message, type) {
    var messageEl = qs(form, '[data-igp-form-message]');
    if (!messageEl) return;
    messageEl.textContent = message || '';
    messageEl.dataset.type = type || '';
  }

  function setLoading(form, isLoading) {
    var buttons = qsa(form, 'button[type="submit"]');
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
          return;
        }

        var data = payload.data || {};
        setMessage(form, data.message || 'Success.', 'success');

        if (data.redirect_url) {
          window.location.href = data.redirect_url;
        }
      })
      .catch(function () {
        setLoading(form, false);
        setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.networkError : 'Network error. Please try again.'), 'error');
      });
  }

  function updateTotal(panel) {
    var currency = (qs(panel, '.igp-booking-total') || {}).dataset ? (qs(panel, '.igp-booking-total').dataset.currency || '₹') : '₹';
    var total = 0;
    var totalGuests = 0;

    qsa(panel, '[data-igp-guest-row] input[type="number"]').forEach(function (input) {
      var qty = Math.max(0, parseInt(input.value || '0', 10) || 0);
      var price = Number(input.dataset.price || '0') || 0;
      total += qty * price;
      totalGuests += qty;
    });

    var option = qs(panel, '[data-igp-tour-option]');
    if (option && option.selectedOptions && option.selectedOptions[0]) {
      total += (Number(option.selectedOptions[0].dataset.price || '0') || 0) * totalGuests;
    }

    qsa(panel, '.igp-booking-addons input[type="checkbox"]:checked').forEach(function (input) {
      total += Number(input.dataset.price || '0') || 0;
    });

    var totalEl = qs(panel, '[data-igp-total]');
    if (totalEl) {
      totalEl.textContent = formatMoney(total, currency);
    }
  }

  function initTabs(panel) {
    var buttons = qsa(panel, '[data-igp-tab]');
    var panels = qsa(panel, '[data-igp-tab-panel]');

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var id = button.dataset.igpTab;
        buttons.forEach(function (btn) {
          var active = btn === button;
          btn.classList.toggle('is-active', active);
          btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (tabPanel) {
          var active = tabPanel.dataset.igpTabPanel === id;
          tabPanel.classList.toggle('is-active', active);
          tabPanel.hidden = !active;
        });
      });
    });
  }

  function initQuantities(panel) {
    panel.addEventListener('click', function (event) {
      var plus = event.target.closest('[data-igp-qty-plus]');
      var minus = event.target.closest('[data-igp-qty-minus]');
      if (!plus && !minus) return;

      var control = event.target.closest('[data-igp-quantity]');
      var input = qs(control, 'input[type="number"]');
      if (!input) return;

      var value = parseInt(input.value || '0', 10) || 0;
      if (plus) value += 1;
      if (minus) value = Math.max(0, value - 1);
      input.value = String(value);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    panel.addEventListener('change', function (event) {
      if (event.target.matches('input, select')) {
        updateTotal(panel);
      }
    });

    panel.addEventListener('input', function (event) {
      if (event.target.matches('input[type="number"]')) {
        updateTotal(panel);
      }
    });

    updateTotal(panel);
  }

  function validateBookingForm(form) {
    var date = qs(form, 'input[name="booking_date"]');
    if (date && !date.value) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseDate : 'Please select a booking date.'), 'error');
      date.focus();
      return false;
    }

    var guests = 0;
    qsa(form, '[data-igp-guest-row] input[type="number"]').forEach(function (input) {
      guests += parseInt(input.value || '0', 10) || 0;
    });

    if (guests < 1) {
      setMessage(form, (window.igpProBooking && window.igpProBooking.i18n ? window.igpProBooking.i18n.chooseGuests : 'Please select at least one traveller.'), 'error');
      return false;
    }

    return true;
  }

  function initForms(panel) {
    var bookingForm = qs(panel, '[data-igp-booking-form]');
    if (bookingForm) {
      bookingForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!validateBookingForm(bookingForm)) return;
        ajaxSubmit(bookingForm);
      });
    }

    var enquiryForm = qs(panel, '[data-igp-enquiry-form]');
    if (enquiryForm) {
      enquiryForm.addEventListener('submit', function (event) {
        event.preventDefault();
        ajaxSubmit(enquiryForm).then(function () {
          // Keep data visible after success; the admin panel is now the source of record.
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
    initTabs(panel);
    initQuantities(panel);
    initForms(panel);
  }

  document.addEventListener('DOMContentLoaded', function () {
    qsa(document, '[data-igp-booking-panel]').forEach(function (panel) {
      initPanel(panel);
      movePanelIntoThemeSidebar(panel);
    });
  });
}());
