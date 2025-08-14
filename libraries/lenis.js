(function (window, document) {
	'use strict';

	/**
	 * Lenis wrapper.
	 * Expects `window.Lenis` constructor to exist when actually using Lenis.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};
	var lenis = null;

	function parseJSONSafe(str, fallback) {
		try { return str ? JSON.parse(str) : fallback; } catch (e) { return fallback; }
	}

	function getOffsetTop(target) {
		if (!target || !target.getBoundingClientRect) { return 0; }
		return target.getBoundingClientRect().top + window.pageYOffset;
	}

	OSS.init = function (settings) {
		var cfg = (settings && settings.lenis) ? settings.lenis : {};
		if (typeof window.Lenis !== 'function') {
			return;
		}
		try {
			var advanced = parseJSONSafe(cfg.custom, {});
			var options = Object.assign({
				duration: (typeof cfg.duration === 'number') ? cfg.duration : 1.2,
				easing: cfg.easing,
				lerp: (typeof cfg.lerp === 'number') ? cfg.lerp : 0.1,
				smoothWheel: !!cfg.smoothWheel,
				smoothTouch: !!cfg.smoothTouch,
				wheelMultiplier: (typeof cfg.wheelMultiplier === 'number') ? cfg.wheelMultiplier : 1,
				touchMultiplier: (typeof cfg.touchMultiplier === 'number') ? cfg.touchMultiplier : 1.5,
				infinite: !!cfg.infinite,
				autoResize: !!cfg.autoResize,
				normalizeWheel: !!cfg.normalizeWheel,
				orientation: cfg.orientation || 'vertical',
				gestureOrientation: cfg.gestureOrientation || 'vertical',
				syncTouch: !!cfg.syncTouch,
				wrapper: cfg.wrapperSelector ? document.querySelector(cfg.wrapperSelector) : undefined,
				content: cfg.contentSelector ? document.querySelector(cfg.contentSelector) : undefined
			}, advanced);

			lenis = new window.Lenis(options);
			function raf(time) { try { lenis.raf(time); } catch (e) {} requestAnimationFrame(raf); }
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