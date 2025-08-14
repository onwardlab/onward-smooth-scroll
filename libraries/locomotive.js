(function (window, document) {
	'use strict';

	/**
	 * Locomotive Scroll wrapper.
	 * Expects `window.LocomotiveScroll` to be available if actually using the library.
	 * This file provides an adapter around our standardized API.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};
	var instance = null;

	function parseJSONSafe(str, fallback) {
		try { return str ? JSON.parse(str) : fallback; } catch (e) { return fallback; }
	}

	function getOffsetTop(target) {
		if (!target || !target.getBoundingClientRect) { return 0; }
		return target.getBoundingClientRect().top + window.pageYOffset;
	}

	OSS.init = function (settings) {
		var cfg = (settings && settings.locomotive) ? settings.locomotive : {};
		if (typeof window.LocomotiveScroll !== 'function') {
			return;
		}
		try {
			var el = document.querySelector(cfg.elSelector || 'body');
			var advanced = parseJSONSafe(cfg.custom, {});
			var options = Object.assign({
				el: el,
				smooth: !!cfg.smooth,
				smoothMobile: !!cfg.smoothMobile,
				lerp: Number(cfg.lerp) || 0,
				multiplier: Number(cfg.multiplier) || 1,
				firefoxMultiplier: Number(cfg.firefoxMultiplier) || 1,
				touchMultiplier: Number(cfg.touchMultiplier) || 2,
				direction: cfg.direction || 'vertical',
				gestureDirection: cfg.gestureDirection || 'vertical',
				class: cfg.class || 'is-inview',
				scrollbarClass: cfg.scrollbarClass || 'c-scrollbar',
				scrollingClass: cfg.scrollingClass || 'has-scroll-scrolling',
				draggingClass: cfg.draggingClass || 'has-scroll-dragging',
				smoothClass: cfg.smoothClass || 'has-smooth',
				initClass: cfg.initClass || 'has-scroll-init',
				getDirection: !!cfg.getDirection,
				scrollFromAnywhere: !!cfg.scrollFromAnywhere,
				reloadOnContextChange: !!cfg.reloadOnContextChange,
				resetNativeScroll: !!cfg.resetNativeScroll,
				tablet: cfg.tablet || {},
				smartphone: cfg.smartphone || {}
			}, advanced);

			instance = new window.LocomotiveScroll(options);

			// Update on resize
			window.addEventListener('resize', function () {
				try { instance.update(); } catch (e) {}
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