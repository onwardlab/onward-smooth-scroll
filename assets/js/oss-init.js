(function (window, document) {
	'use strict';

	/**
	 * Orchestrates smooth scrolling initialization using the active library wrapper.
	 * Expects `window.ossSettings` localized via WordPress.
	 * The wrapper should expose `window.OSS_SmoothScroll` with { init(settings), scrollTo(targetOrY, options) }.
	 * If absent, a native fallback is used.
	 */
	var settings = window.ossSettings || {};

	// Provide a safe default API in case wrapper is missing.
	var DefaultAPI = {
		init: function (cfg) {
			// Enable CSS smooth scroll via class toggle.
			document.documentElement.classList.add('oss-native-smooth');
		},
		scrollTo: function (targetOrY, options) {
			var offset = options && typeof options.offset === 'number' ? options.offset : 0;
			var y = typeof targetOrY === 'number'
				? targetOrY
				: (targetOrY && targetOrY.getBoundingClientRect ? (targetOrY.getBoundingClientRect().top + window.pageYOffset) : 0);
			window.scrollTo({ top: Math.max(0, y - offset), behavior: 'smooth' });
		}
	};

	var API = window.OSS_SmoothScroll || DefaultAPI;

	function handleAnchorClick(event) {
		var link = event.currentTarget;
		if (!link) { return; }
		var href = link.getAttribute('href');
		if (!href || href.charAt(0) !== '#') { return; }
		var id = href.slice(1);
		var target = document.getElementById(decodeURIComponent(id)) || document.querySelector(href);
		if (!target) { return; }
		event.preventDefault();
		try {
			API.scrollTo(target, { offset: Number(settings.anchorOffset) || 0, easing: settings.easing });
		} catch (e) {
			DefaultAPI.scrollTo(target, { offset: Number(settings.anchorOffset) || 0, easing: settings.easing });
		}
	}

	function bindAnchorLinks() {
		var links = document.querySelectorAll('a[href^="#"]');
		for (var i = 0; i < links.length; i++) {
			var href = links[i].getAttribute('href');
			if (href && href !== '#') {
				links[i].addEventListener('click', handleAnchorClick, false);
			}
		}
	}

	function init() {
		try {
			if (API && typeof API.init === 'function') {
				API.init(settings);
			}
		} catch (e) {}

		bindAnchorLinks();

		// If the page loads with a hash, scroll to it with offset.
		if (window.location.hash) {
			var target = document.getElementById(window.location.hash.substring(1));
			if (target) {
				setTimeout(function () {
					API.scrollTo(target, { offset: Number(settings.anchorOffset) || 0 });
				}, 0);
			}
		}

		// Broadcast an event for consumers.
		try {
			window.dispatchEvent(new CustomEvent('oss:initialized', { detail: { settings: settings } }));
		} catch (e) {}
	}

	if (document.readyState === 'complete' || document.readyState === 'interactive') {
		setTimeout(init, 0);
	} else {
		document.addEventListener('DOMContentLoaded', init);
	}

})(window, document);