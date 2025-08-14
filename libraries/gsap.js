(function (window, document) {
	'use strict';

	/**
	 * GSAP / ScrollTrigger wrapper.
	 * Requires `window.gsap` and optionally `ScrollToPlugin` and `ScrollTrigger` if used.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};

	function parseJSONSafe(str, fallback) {
		try { return str ? JSON.parse(str) : fallback; } catch (e) { return fallback; }
	}

	OSS.init = function (settings) {
		var gsap = window.gsap;
		if (!gsap) { return; }

		var cfg = (settings && settings.gsap) ? settings.gsap : {};
		var std = cfg.scrollTriggerDefaults || {};

		// Configure ScrollTrigger defaults if plugin exists.
		if (gsap.core && gsap.core.globals && gsap.core.globals().ScrollTrigger) {
			var ScrollTrigger = gsap.core.globals().ScrollTrigger;
			try { ScrollTrigger.defaults(std); } catch (e) {}
		}

		// Initialize declared triggers from JSON.
		var triggers = parseJSONSafe(cfg.triggersJSON, []);
		if (Array.isArray(triggers) && triggers.length && gsap.to) {
			var ST = (gsap.core && gsap.core.globals) ? gsap.core.globals().ScrollTrigger : window.ScrollTrigger;
			for (var i = 0; i < triggers.length; i++) {
				var t = triggers[i] || {};
				var anim = t.animation || {};
				var tween;
				if (anim.from) { tween = gsap.from(t.target || t.trigger, anim.from); }
				if (anim.to)   { tween = gsap.to(t.target || t.trigger, anim.to); }
				if (ST && t.trigger) {
					var trigCfg = Object.assign({}, t);
					delete trigCfg.animation; delete trigCfg.target;
					trigCfg.animation = tween || null;
					try { ST.create(trigCfg); } catch (e) {}
				}
			}
		}
	};

	OSS.scrollTo = function (targetOrY, options) {
		var gsap = window.gsap;
		var cfg = (window.ossSettings && window.ossSettings.gsap) ? window.ossSettings.gsap : {};
		var offset = options && typeof options.offset === 'number' ? options.offset : (cfg.offset || 0);

		var y = (typeof targetOrY === 'number')
			? targetOrY
			: (targetOrY && targetOrY.getBoundingClientRect ? (targetOrY.getBoundingClientRect().top + window.pageYOffset) : 0);

		if (gsap && cfg.enableScrollTo && (gsap.plugins && gsap.plugins.ScrollToPlugin)) {
			try {
				gsap.to(window, {
					duration: (typeof cfg.duration === 'number') ? cfg.duration : 1,
					ease: cfg.ease || 'power2.out',
					scrollTo: { y: Math.max(0, y - offset), autoKill: !!cfg.autoKill },
					overwrite: !!cfg.overwrite
				});
				return;
			} catch (e) {}
		}
		window.scrollTo({ top: Math.max(0, y - offset), behavior: 'smooth' });
	};

})(window, document);