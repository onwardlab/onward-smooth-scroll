(function ($) {
	'use strict';

	/**
	 * Admin UI for Onward Smooth Scrolling settings.
	 * - Handles tab switching between General and Library sections
	 * - Shows only the library-specific section for the selected library
	 */
	function setActiveTab(targetSelector) {
		$('.nav-tab').removeClass('nav-tab-active');
		$(".nav-tab[href='" + targetSelector + "']").addClass('nav-tab-active');
		$('.oss-tab-content').removeClass('active');
		$(targetSelector).addClass('active');
	}

	function updateLibrarySections() {
		var value = $('#oss_active_library').val();
		$('.oss-library-options').removeClass('active').hide();
		$('.oss-library-options[data-library="' + value + '"]').addClass('active').show();
	}

	$(document).on('click', '#oss-tabs .nav-tab', function (e) {
		e.preventDefault();
		var target = $(this).data('target') || $(this).attr('href');
		setActiveTab(target);
	});

	$(document).on('change', '#oss_active_library', function () {
		updateLibrarySections();
	});

	$(function () {
		// Initialize UI state on load.
		updateLibrarySections();
	});

})(jQuery);