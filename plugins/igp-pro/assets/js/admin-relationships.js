(function () {
	'use strict';

	function initObjectPicker() {
		var picker = document.getElementById('igp-pro-relationship-object');
		if (!picker) {
			return;
		}

		picker.addEventListener('change', function () {
			if (picker.form) {
				picker.form.submit();
			}
		});
	}

	function initMultiSelectHints() {
		var selects = document.querySelectorAll('.igp-pro-relationship-select');
		selects.forEach(function (select) {
			select.setAttribute('aria-describedby', select.getAttribute('aria-describedby') || '');
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initObjectPicker();
			initMultiSelectHints();
		});
	} else {
		initObjectPicker();
		initMultiSelectHints();
	}
}());
