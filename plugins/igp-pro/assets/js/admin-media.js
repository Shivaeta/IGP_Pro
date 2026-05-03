(function () {
	'use strict';
	if (!document || !document.querySelector) {
		return;
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.querySelector) {
			return;
		}

		var webp = form.querySelector('input[name="action"][value="igp_pro_generate_webp"]');
		var bulk = form.querySelector('input[name="action"][value="igp_pro_media_bulk_image_update"]');
		var button = form.querySelector('input[type="submit"], button[type="submit"]');

		if (webp && button) {
			button.disabled = true;
			button.value = 'Generating…';
		}

		if (bulk) {
			var hasPotentialFileEdit = false;
			form.querySelectorAll('input[name*="[width]"], input[name*="[height]"]').forEach(function (input) {
				if (input.value && parseInt(input.value, 10) > 0) {
					hasPotentialFileEdit = true;
				}
			});
			form.querySelectorAll('select[name*="[aspect_ratio]"]').forEach(function (select) {
				if (select.value && select.value !== 'keep') {
					hasPotentialFileEdit = true;
				}
			});

			if (hasPotentialFileEdit && !window.confirm('Resize/aspect-ratio edits create new image files and update the WordPress attachment reference. Continue?')) {
				event.preventDefault();
				return;
			}

			if (button) {
				button.disabled = true;
				button.value = 'Applying edits…';
			}
		}
	});
}());
