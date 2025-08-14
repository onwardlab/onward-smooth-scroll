(function (window, document) {
	'use strict';

	/**
	 * Locomotive Scroll wrapper.
	 * Expects `window.LocomotiveScroll` to be available if actually using the library.
	 * This file only provides a thin adapter around our standardized API.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};
	var instance = null;

	function getOffsetTop(target) {
		if (!target || !target.getBoundingClientRect) { return 0; }
		return target.getBoundingClientRect().top + window.pageYOffset;
	}

	OSS.init = function (settings) {
		if (typeof window.LocomotiveScroll !== 'function') {
			// Fallback silently; initializer will still bind anchors.
			return;
		}
		try {
			instance = new window.LocomotiveScroll({
				smooth: true,
				multiplier: Number(settings.speed) || 1,
				easing: settings.easing || 'ease',
			});
		} catch (e) {
			instance = null;
		}
	};

	OSS.scrollTo = function (targetOrY, options) {
		var offset = options && typeof options.offset === 'number' ? options.offset : 0;
		var y = typeof targetOrY === 'number' ? targetOrY : getOffsetTop(targetOrY);
		if (instance && typeof instance.scrollTo === 'function') {
			instance.scrollTo(y - offset, { duration: 1000 });
			return;
		}
		window.scrollTo({ top: Math.max(0, y - offset), behavior: 'smooth' });
	};

})(window, document);