'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');

var quizJsFiles = [
	'./assets/admin/js/quiz/models/question.js',
	'./assets/admin/js/quiz/models/multiple-choice-answer.js',
	'./assets/admin/js/quiz/collections/questions.js',
	'./assets/admin/js/quiz/collections/multiple-choice-answers.js',
	'./assets/admin/js/quiz/views/multiple-choice-answer.js',
	'./assets/admin/js/quiz/views/question.js',
	'./assets/admin/js/quiz/views/multiple-choice-question.js',
	'./assets/admin/js/quiz/views/written-answer-question.js',
	'./assets/admin/js/quiz/views/file-upload-question.js',
	'./assets/admin/js/quiz/views/quiz.js',
	'./assets/admin/js/quiz/quiz.js'
];

gulp.task('quizjs', function() {
	var stream = gulp.src(quizJsFiles).pipe(concat('quiz.min.js'));

	stream.pipe(gulp.dest('./assets/admin/js/quiz'));
});

gulp.task('watch', function() {
	gulp.watch(quizJsFiles, ['quizjs']);
});

gulp.task('default', ['quizjs']);
