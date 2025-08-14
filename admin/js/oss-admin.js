(function ($) {
	'use strict';

	function updateLibrarySections() {
		var value = $('#oss_active_library').val();
		$('.oss-library-options').removeClass('active').hide();
		$('.oss-library-options[data-library="' + value + '"]').addClass('active').show();
	}

	$(document).on('change', '#oss_active_library', function () {
		updateLibrarySections();
	});

	$(function () {
		updateLibrarySections();
	});

})(jQuery);