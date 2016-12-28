/**
 * Multiple choice answer view.
 */
(function(exports, $) {
	'use strict';

	var MultipleChoiceAnswerView = Backbone.View.extend({
		/** @member {string} */
		className: 'edr-answer-mc',

		/** @member {string} */
		tagName: 'tr',

		/** @member {Function} */
		template: _.template($('#edr-tpl-multiplechoiceanswer').html()),

		/** @member {Object} */
		events: {
			'click .delete-answer': 'deleteAnswer'
		},

		/**
		 * Initialize view.
		 *
		 * @param {Object} options
		 */
		initialize: function(options) {
			this.listenTo(this.model, 'remove', this.remove);
			this.listenTo(this.model, 'updateAnswerValues', this.updateAnswerValues);
			this.listenTo(this.model, 'updateOrderFromView', this.updateOrderFromView);
		},

		/**
		 * Render view.
		 *
		 * @return MultipleChoiceAnswerView
		 */
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));

			if (this.model.get('correct') === 1) {
				this.$el.find('.answer-correct').attr('checked', 'checked');
			}

			return this;
		},

		/**
		 * Update model from view.
		 */
		updateAnswerValues: function() {
			this.model.set('choice_text', this.$el.find('.answer-text').val());
			this.model.set('correct', this.$el.find('.answer-correct').is(':checked') ? 1 : 0);
		},

		/**
		 * Delete answer.
		 *
		 * @param {Object} e
		 */
		deleteAnswer: function(e) {
			if (confirm(EdrQuiz.text.confirmDelete)) {
				this.model.destroy();
			}

			e.preventDefault();
		},

		/**
		 * Update menu order from view.
		 */
		updateOrderFromView: function() {
			this.model.set('menu_order', this.$el.index());
		}
	});

	exports.MultipleChoiceAnswerView = MultipleChoiceAnswerView;
})(EdrQuiz, jQuery);
