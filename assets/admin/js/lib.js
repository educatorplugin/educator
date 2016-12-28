window.EdrLib = window.EdrLib || {};

EdrLib.transitionEnd = (function() {
	var eventName = null;

	return function() {
		var element;

		if (eventName === null) {
			element = document.createElement('div');
			eventName = (element.style['transition'] !== undefined) ? 'transitionend' : '';
		}

		return eventName;
	};
})();

EdrLib.Notify = (function($) {
	'use strict';

	var templates = {
		noticeSuccess: '<div class="edr-notice edr-notice_success"><span class="dashicons dashicons-yes"></span></div>',
		noticeError: '<div class="edr-notice edr-notice_error"><span class="dashicons dashicons-no"></span></div>'
	};

	var hideNotice = function(noticeEl) {
		var transitionEnd = EdrLib.transitionEnd();
		var complete = function() {
			noticeEl.remove();
		};

		if (transitionEnd) {
			noticeEl.one(transitionEnd, complete);
			noticeEl.removeClass('edr-notice_visible');
		} else {
			complete();
		}
	};

	return {
		showNotice: function(element, status) {
			var elementOffset = element.offset();
			var template = (status === 'success') ? templates.noticeSuccess : templates.noticeError;
			var noticeEl = $(template);
			var left;
			var top;

			left = elementOffset.left + element.outerWidth();
			top = elementOffset.top + (element.outerHeight() / 2);

			noticeEl.css({
				left: left + 'px',
				top: top + 'px'
			});

			noticeEl.appendTo('body').get(0).offsetWidth;

			noticeEl.addClass('edr-notice_visible');

			setTimeout(function() {
				hideNotice(noticeEl);
			}, 1000);
		},

		success: function(element) {
			this.showNotice(element, 'success');
		},

		error: function(element) {
			this.showNotice(element, 'error');
		}
	};
})(jQuery);
