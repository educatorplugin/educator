/**
 * Question view.
 */
(function(exports, $) {
	'use strict';

	var QuestionView = Backbone.View.extend({
		/** @member {string} */
		tagName: 'div',

		/** @member {Object} */
		events: {
			'click .edr-question__header': 'triggerQuestion',
			'click .save-question':        'saveQuestion',
			'keypress':                    'onKeyPress',
			'click .delete-question':      'deleteQuestion'
		},

		/**
		 * Initilize view.
		 */
		initialize: function() {
			// Setup model listeners.
			this.model.on('change:question', this.updateQuestionLabel, this);
			this.model.on('remove', this.remove, this);
			this.model.on('updateOrderFromView', this.updateOrderFromView, this);
			this.model.on('sync', this.afterSave, this);
		},

		/**
		 * Render view.
		 */
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
		},

		/**
		 * Update question label.
		 */
		updateQuestionLabel: function() {
			this.$el.find('.edr-question__label').text(this.model.get('question'));
		},

		/**
		 * Update the question's menu order from view.
		 */
		updateOrderFromView: function() {
			this.model.set('menu_order', this.$el.index());
		},

		/**
		 * Open/close the question.
		 *
		 * @param {Object} e
		 */
		triggerQuestion: function(e) {
			if (this.isOpen()) {
				this.closeQuestion();
			} else {
				this.openQuestion();
			}

			e.preventDefault();
		},

		/**
		 * Open the question.
		 */
		openQuestion: function() {
			this.$el.addClass('edr-question_open');
		},

		/**
		 * Close the question.
		 */
		closeQuestion: function() {
			this.$el.removeClass('edr-question_open');
		},

		/**
		 * Check if the question is open.
		 */
		isOpen: function() {
			return this.$el.hasClass('edr-question_open');
		},

		/**
		 * Delete the question.
		 *
		 * @param {Object} e
		 */
		deleteQuestion: function(e) {
			if (confirm(EdrQuiz.text.confirmDelete)) {
				this.model.destroy({wait: true});
			}

			e.preventDefault();
		},

		/**
		 * Show the "success" message when the question is saved.
		 *
		 * @param {QuestionModel} model
		 * @param {Object} response
		 * @param {Object} options
		 */
		afterSave: function(model, response, options) {
			this.showMessage('saved', 800);

			this.$el.find('.save-question').removeAttr('disabled');
		},

		/**
		 * Disable the "Save Question" button when saving the question.
		 */
		saveQuestion: function() {
			this.$el.find('.save-question').attr('disabled', 'disabled');
		},

		/**
		 * Save the question when the "enter" key is pressed.
		 *
		 * @param {Object} e
		 */
		onKeyPress: function(e) {
			if (e.which === 13 && e.target.nodeName !== 'TEXTAREA') {
				e.stopPropagation();

				if (!this.$el.find('.save-question').attr('disabled')) {
					this.saveQuestion(e);
				}
			}
		},

		/**
		 * Show a message.
		 *
		 * @param {string} type
		 * @param {number} timeout
		 */
		showMessage: function(type, timeout) {
			timeout || (timeout = 0);
			var that = this;
			var classes = 'edr-overlay';

			switch (type) {
				case 'saved':
					classes += ' edr-overlay_saved';
					break;

				case 'error':
					classes += ' edr-overlay_error';
					break;
			}

			var show = function() {
				var message = $('<div class="' + classes + '"></div>');

				message.hide();

				that.$el.find('.edr-question__body').append(message);
				
				message.fadeIn(200);

				if (timeout) {
					setTimeout(function() {
						that.hideMessage();
					}, timeout);
				}
			};

			if (this.$el.find('.edr-question__message').length) {
				this.hideMessage(show);
			} else {
				show();
			}
		},

		/**
		 * Hide the message.
		 *
		 * @param {Function} cb
		 */
		hideMessage: function(cb) {
			this.$el.find('.edr-overlay').fadeOut(200, function() {
				$(this).remove();

				if (typeof cb === 'function') {
					cb.apply();
				}
			});
		}
	});

	exports.QuestionView = QuestionView;
})(EdrQuiz, jQuery);
