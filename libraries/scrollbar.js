(function (window, document) {
	'use strict';

	/**
	 * Smooth Scrollbar wrapper.
	 * Expects `window.Scrollbar` namespace (from smooth-scrollbar) when used.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};
	var scrollbar = null;

	function getOffsetTop(target) {
		if (!target || !target.getBoundingClientRect) { return 0; }
		return target.getBoundingClientRect().top + window.pageYOffset;
	}

	OSS.init = function (settings) {
		if (!window.Scrollbar || typeof window.Scrollbar.init !== 'function') {
			return;
		}
		try {
			// Initialize on body wrapper. Many setups use a dedicated container; developers can override via hooks.
			var container = document.querySelector('[data-scrollbar]') || document.body;
			scrollbar = window.Scrollbar.init(container, {
				damping: Math.max(0.01, Math.min(1, 1 / ((Number(settings.speed) || 1) * 10)))
			});
		} catch (e) { scrollbar = null; }
	};

	OSS.scrollTo = function (targetOrY, options) {
		var offset = options && typeof options.offset === 'number' ? options.offset : 0;
		var y = typeof targetOrY === 'number' ? targetOrY : getOffsetTop(targetOrY);
		if (scrollbar && typeof scrollbar.scrollTo === 'function') {
			scrollbar.scrollTo(0, Math.max(0, y - offset), 600);
			return;
		}
		window.scrollTo({ top: Math.max(0, y - offset), behavior: 'smooth' });
	};

})(window, document);