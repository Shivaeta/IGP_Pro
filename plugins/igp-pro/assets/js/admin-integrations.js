(function () {
	'use strict';

	if (!window.wp || !window.wp.hooks || !window.igpRankMathBridge) {
		return;
	}

	window.wp.hooks.addFilter('rank_math_title', 'igp-pro/rank-math-title', function (title) {
		return title || window.igpRankMathBridge.title || '';
	});

	window.wp.hooks.addFilter('rank_math_content', 'igp-pro/rank-math-content', function (content) {
		var bridge = window.igpRankMathBridge || {};
		var extra = [bridge.content, bridge.imageContent, bridge.linkAnalysisContent]
			.filter(function (value) { return typeof value === 'string' && value.trim() !== ''; })
			.join('\n\n');

		return extra ? String(content || '') + '\n\n' + extra : content;
	});
}());
