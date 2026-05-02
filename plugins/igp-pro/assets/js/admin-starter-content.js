(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var textareas = document.querySelectorAll('.igp-pro-starter-content-admin textarea[readonly]');
		textareas.forEach(function (textarea) {
			textarea.addEventListener('focus', function () {
				textarea.select();
			});
		});
	});
}());
