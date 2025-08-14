(function (window, document) {
	'use strict';

	/**
	 * Native CSS smooth scroll wrapper.
	 * Provides a consistent API used by the initializer.
	 */
	var OSS = window.OSS_SmoothScroll = window.OSS_SmoothScroll || {};

	OSS.init = function (settings) {
		document.documentElement.classList.add('oss-native-smooth');
	};

	OSS.scrollTo = function (targetOrY, options) {
		var offset = options && typeof options.offset === 'number' ? options.offset : 0;
		var y = typeof targetOrY === 'number'
			? targetOrY
			: (targetOrY && targetOrY.getBoundingClientRect ? (targetOrY.getBoundingClientRect().top + window.pageYOffset) : 0);
		window.scrollTo({ top: Math.max(0, y - offset), behavior: 'smooth' });
	};

})(window, document);