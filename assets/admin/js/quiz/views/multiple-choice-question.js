/**
 * Multiple choice question view.
 */
(function(exports, $) {
	'use strict';

	var MultipleChoiceQuestionView = EdrQuiz.QuestionView.extend({
		/** @member {string} */
		className: 'edr-question edr-question_multiple',

		/** @member {Function} */
		template: _.template($('#edr-tpl-multiplechoicequestion').html()),

		/**
		 * Register events.
		 */
		events: function() {
			return _.extend({
				'click .add-answer':  'addChoice',
				'updateChoicesOrder': 'updateChoicesOrder'
			}, this.constructor.__super__.events);
		},

		/**
		 * Initialize view.
		 */
		initialize: function() {
			// Initialize the parent object.
			EdrQuiz.QuestionView.prototype.initialize.apply(this);

			// Create collection.
			this.collection = new EdrQuiz.MultipleChoiceAnswersCollection();

			// Remove a choice from the view when its removed from the collection.
			this.listenTo(this.collection, 'remove', this.onChoiceRemove);

			// Set initial choices.
			var id = this.model.get('id');

			if (educatorQuizChoices['question_' + id]) {
				var choices = educatorQuizChoices['question_' + id],
				    i;

				for (i = 0; i < choices.length; i++) {
					this.collection.add(choices[i]);
				}
			}
		},

		/**
		 * Render view.
		 */
		render: function() {
			this.$el.find('.js-edr-answers').sortable('destroy');

			EdrQuiz.QuestionView.prototype.render.apply(this);

			var answersEl = this.$el.find('.js-edr-answers');

			if (this.collection.length > 0) {
				this.$el.find('.no-answers').hide();
				this.$el.find('.edr-question__answers > table > thead').show();

				var choicesFragment = document.createDocumentFragment();
				this.collection.each(function(choice) {
					var view = new EdrQuiz.MultipleChoiceAnswerView({model: choice});
					choicesFragment.appendChild(view.render().el);
				});
				answersEl.append(choicesFragment);
			}

			answersEl.sortable({
				axis: 'y',
				items: 'tr',
				handle: 'div.handle',
				placeholder: 'placeholder',
				helper: function(e, helper) {
					helper.children().each(function(i) {
						var td = $(this);
						td.width(td.innerWidth());
					});

					return helper;
				},
				start: function(e, ui) {
					ui.placeholder.height(ui.item.height() - 2);
				},
				update: function(e, ui) {
					$(this).trigger('updateChoicesOrder');
				},
				stop: function(e, ui) {
					ui.item.children().removeAttr('style');
				}
			});
		},

		/**
		 * Hide the choices table if the choices collection is empty.
		 */
		onChoiceRemove: function() {
			if (this.collection.length < 1) {
				this.$el.find('.no-answers').show();
				this.$el.find('.edr-question__answers > table > thead').hide();
			}
		},

		/**
		 * Process the "add answer" event.
		 *
		 * @param {Object} e
		 */
		addChoice: function(e) {
			var choice = new EdrQuiz.MultipleChoiceAnswerModel();
			var maxMenuOrder = 0;

			_.each(this.collection.models, function(choice) {
				if (choice.get('menu_order') > maxMenuOrder) {
					maxMenuOrder = choice.get('menu_order');
				}
			});

			choice.set('menu_order', maxMenuOrder + 1);

			this.collection.add(choice);

			var view = new EdrQuiz.MultipleChoiceAnswerView({model: choice});
			this.$el.find('.js-edr-answers').append(view.render().$el)

			// Hide "no answers" message.
			this.$el.find('.no-answers').hide();

			e.preventDefault();
		},

		/**
		 * Update the menu order of each choice.
		 */
		updateChoicesOrder: function() {
			_.each(this.collection.models, function(choice) {
				choice.trigger('updateOrderFromView');
			});
		},

		/**
		 * Save question.
		 *
		 * @param {Object} e
		 */
		saveQuestion: function(e) {
			var that = this;
			var newData = {};

			this.lockQuestion();

			// Setup question data.
			newData.question = this.$el.find('.question-text').val();
			newData.question_content = this.$el.find('.question-content').val();
			newData.optional = this.$el.find('.question-optional').prop('checked') ? 1 : 0;
			newData.menu_order = this.$el.index();
			
			// Setup choices.
			newData.choices = [];

			_.each(this.collection.models, function(choice) {
				choice.trigger('updateAnswerValues');
				newData.choices.push({
					choice_id:   choice.get('choice_id'),
					choice_text: choice.get('choice_text'),
					correct:     choice.get('correct'),
					menu_order:  choice.get('menu_order')
				});
			});

			// Send request to the server.
			this.model.save(newData, {
				wait: true,
				success: function(model, response, options) {
					if (response.status === 'success') {
						that.collection.remove(that.collection.models);

						if (response.choices) {
							that.collection.reset(response.choices);
						}

						that.render();
						that.showMessage('saved', 800);
					}
				},
				error: function(model, xhr, options) {
					that.showMessage('error', 800);
				},
				complete: function() {
					that.unlockQuestion();
				}
			});

			e.preventDefault();
		}
	});

	exports.MultipleChoiceQuestionView = MultipleChoiceQuestionView;
}(EdrQuiz, jQuery));
