(function () {
	'use strict';
	if (!document || !document.querySelector) {
		return;
	}
	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.querySelector('input[name="action"][value="igp_pro_generate_webp"]')) {
			return;
		}
		var button = form.querySelector('input[type="submit"], button[type="submit"]');
		if (button) {
			button.disabled = true;
			button.value = 'Generating…';
		}
	});
}());
