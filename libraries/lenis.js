(function (window, document) {
	'use strict';

	/**
	 * Lenis wrapper.
	 * Expects `window.Lenis` constructor to exist when actually using Lenis.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};
	var lenis = null;

	function getOffsetTop(target) {
		if (!target || !target.getBoundingClientRect) { return 0; }
		return target.getBoundingClientRect().top + window.pageYOffset;
	}

	OSS.init = function (settings) {
		if (typeof window.Lenis !== 'function') {
			return;
		}
		try {
			lenis = new window.Lenis({
				smoothWheel: true,
				smoothTouch: !!settings.enableMobile,
				lerp: (typeof settings.speed === 'number' && settings.speed > 0) ? Math.min(0.99, Math.max(0.01, 1 / (settings.speed * 10))) : 0.1,
			});
			function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
			requestAnimationFrame(raf);
		} catch (e) { lenis = null; }
	};

	OSS.scrollTo = function (targetOrY, options) {
		var offset = options && typeof options.offset === 'number' ? options.offset : 0;
		var y = typeof targetOrY === 'number' ? targetOrY : getOffsetTop(targetOrY);
		if (lenis && typeof lenis.scrollTo === 'function') {
			lenis.scrollTo(y - offset);
			return;
		}
		window.scrollTo({ top: Math.max(0, y - offset), behavior: 'smooth' });
	};

})(window, document);