(function($) {
	'use strict';

	var saveQuizGrade = function(form) {
		var gradeId;
		var grade;
		var nonce;
		var button;

		button = form.querySelector('.save-quiz-grade');

		if (button.getAttribute('disabled')) {
			return;
		}

		button.setAttribute('disabled', 'disabled');

		gradeId = form.querySelector('.input-grade-id').value;
		grade = form.querySelector('.input-grade').value;
		nonce = form.querySelector('.input-nonce').value;

		$.ajax({
			cache: false,
			method: 'post',
			dataType: 'json',
			url: ajaxurl + '?action=edr_edit_quiz_grade',
			data: {
				grade_id: gradeId,
				grade:    grade,
				_wpnonce: nonce
			},
			success: function(response) {
				button.removeAttribute('disabled');

				if (response && response.status && response.status === 'success') {
					EdrLib.Notify.success($(button));
				} else {
					EdrLib.Notify.error($(button));
				}
			},
			error: function() {
				button.removeAttribute('disabled');
			}
		});
	};

	var quizGradeFormEls = $('.edr-quiz-grade-form');

	quizGradeFormEls.on('click', '.save-quiz-grade', function(e) {
		e.preventDefault();
		saveQuizGrade(e.delegateTarget);
	});

	quizGradeFormEls.on('keydown', '.input-grade', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			saveQuizGrade(e.delegateTarget);
		}
	});

	$('.edr-quiz-grade__title').on('click', function() {
		$(this).parent().toggleClass('edr-quiz-grade_closed');
	});
})(jQuery);
