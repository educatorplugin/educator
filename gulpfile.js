'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');

var quizJsFiles = [
	'./assets/js/quiz/models/question.js',
	'./assets/js/quiz/models/multiple-choice-answer.js',
	'./assets/js/quiz/collections/questions.js',
	'./assets/js/quiz/collections/multiple-choice-answers.js',
	'./assets/js/quiz/views/multiple-choice-answer.js',
	'./assets/js/quiz/views/question.js',
	'./assets/js/quiz/views/multiple-choice-question.js',
	'./assets/js/quiz/views/written-answer-question.js',
	'./assets/js/quiz/views/file-upload-question.js',
	'./assets/js/quiz/views/quiz.js',
	'./assets/js/quiz/quiz.js'
];

gulp.task('quizjs', function() {
	var stream = gulp.src(quizJsFiles).pipe(concat('quiz.min.js'));

	stream.pipe(gulp.dest('./assets/js/quiz'));
});

gulp.task('watch', function() {
	gulp.watch(quizJsFiles, ['quizjs']);
});

gulp.task('default', ['quizjs']);
