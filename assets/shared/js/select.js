/**
 * Select.
 *
 * Version 1.0.0
 */
window.EdrLib = window.EdrLib || {};

EdrLib.select = (function($) {
	function Select(input, options) {
		this.input = $(input);
		this.options = options;
		this.running = false;
		this.ajaxRequest = null;
		this.ajaxTimeout = null;
		this.currentFilterValue = null;
		this.items = [];
		this.selectedItems = [];
		this.disabled = false;
		this.uniqueId = 1;

		this.init();
	}

	/**
	 * Initialize.
	 */
	Select.prototype.init = function() {
		var value, label, item;

		this.input.attr('type', 'hidden');
		this.input.addClass('edr-select-input');
		this.trigger = $('<div class="selected-values"></div>');
		this.trigger.insertBefore(this.input);
		this.choicesDiv = $('<div class="edr-select-choices"><div class="choices-filter"><input type="text"></div><div class="choices"></div></div>');
		this.choicesDiv.appendTo('body');

		value = this.input.val();
		label = this.input.data('label');

		if (value !== '') {
			item = {};
			item[this.options.key] = value;
			item[this.options.label] = (label !== '') ? label : value;
			this.selectedItems.push(item);
		}

		this.renderSelectedItems();

		if (this.input.prop('disabled')) {
			this.disable();
		}

		if (this.options.items && this.options.items.length) {
			this.items = this.options.items;
			this.assignIdsToItems(this.items);
		}

		this.initEvents();
	};

	/**
	 * Initialize events.
	 */
	Select.prototype.initEvents = function() {
		var that = this;

		this.trigger.on('click', function(e) {
			that.fetchAndDisplayChoices(0);

			e.preventDefault();
			e.stopPropagation();
		});

		this.trigger.on('click', '.remove-value', function(e) {
			var key;

			if (!that.disabled) {
				key = $(this).parent().attr('data-key');
				that.removeItem(key);
			}

			e.stopPropagation();
			e.preventDefault();
		});

		this.choicesDiv.on('keyup', 'input', function(e) {
			var item, jEvent;

			if (that.options.allowNewValues && e.which === 13) {
				if (!that.ajaxTimeout && !that.running) {
					item = {};
					item[that.options.key] = this.value;
					item[that.options.label] = this.value;
					that.addItem(item, {isNew: true});
					that.clearChoices();
				}
			} else {
				that.fetchAndDisplayChoices(800);
			}
		});

		this.choicesDiv.on('click', function(e) {
			e.stopPropagation();
		});

		this.choicesDiv.on('click', '.choice', function(e) {
			var key = this.getAttribute('data-key');
			var itemIndex = that.getItemIndex(key, 'allItems');
			var item = (itemIndex > -1) ? that.items[itemIndex] : null;

			if (item) {
				that.addItem(item, {isNew: false});
				that.clearChoices();
			}

			e.preventDefault();
			e.stopPropagation();
		});

		$('body').on('click.edrSelect', function() {
			that.clearChoices();
		});
	};

	/**
	 * Disable the select box.
	 */
	Select.prototype.disable = function() {
		this.disabled = true;
		this.input.parent().addClass('edr-select-values_disabled');
	};

	/**
	 * Enable the select box.
	 */
	Select.prototype.enable = function() {
		this.disabled = false;
		this.input.parent().removeClass('edr-select-values_disabled');
	};

	/**
	 * Get selected items.
	 *
	 * @return {array}
	 */
	Select.prototype.getSelectedItems = function() {
		return this.selectedItems;
	};

	/**
	 * Show spinner.
	 */
	Select.prototype.showSpinner = function() {
		var spinner = this.choicesDiv.find('.edr-spinner');

		if (!spinner.length) {
			spinner = $('<span class="edr-spinner"></span>');
			this.choicesDiv.find('.choices-filter').append(spinner);
		}

		spinner.addClass('edr-spinner_visible');
	};

	/**
	 * Hide spinner.
	 */
	Select.prototype.hideSpinner = function() {
		var spinner = this.choicesDiv.find('.edr-spinner');

		spinner.removeClass('edr-spinner_visible');
	};

	/**
	 * Get index of an item.
	 *
	 * @param {string} key
	 * @param {string} from allItems or selectedItems
	 * @return {number}
	 */
	Select.prototype.getItemIndex = function(key, from) {
		var i, items, length, itemKey;

		items = (from === 'selectedItems') ? this.selectedItems : this.items;

		for (i = 0, length = items.length; i < length; i++) {
			itemKey = items[i][this.options.key] + '';

			if (itemKey === key) {
				return i;
			}
		}

		return -1;
	},

	/**
	 * Add item.
	 *
	 * @param {object} item
	 * @param {object} args
	 */
	Select.prototype.addItem = function(item, args) {
		var key = item[this.options.key];
		var index = this.getItemIndex(key, 'selectedItems');
		var jEvent;

		// Add item only if it doesn't exist.
		if (index < 0) {
			jEvent = $.Event('edr.select.add');

			this.input.trigger(jEvent, {
				item: item,
				isNew: args.isNew
			});

			if (jEvent.isDefaultPrevented()) {
				return;
			}

			this.selectedItems = [item];
			this.input.val(key);
			this.renderSelectedItems();
		}
	};

	/**
	 * Remove item.
	 *
	 * @param {string} key
	 */
	Select.prototype.removeItem = function(key) {
		var index = this.getItemIndex(key, 'selectedItems');

		if (index > -1) {
			this.selectedItems.splice(index, 1);
			this.input.val('');
			this.renderSelectedItems();
		}
	};

	/**
	 * Clear items.
	 */
	Select.prototype.clearItems = function() {
		this.selectedItems = [];
		this.input.val('');
		this.renderSelectedItems();
	};

	/**
	 * Get search filter value.
	 */
	Select.prototype.getFilterValue = function() {
		return this.choicesDiv.find('.choices-filter > input').val();
	};

	/**
	 * Render selected item.
	 *
	 * @param {object} item
	 */
	Select.prototype.getItemHtml = function(item) {
		var key = item[this.options.key];
		var label = item[this.options.label];
		var html;

		html = '<div class="selected-value" data-key="' + key + '"><span>' + label + '</span>';
		html += '<button type="button" class="remove-value">&times;</button>';
		html += '</div>';

		return html;
	};

	/**
	 * Render selected items.
	 */
	Select.prototype.renderSelectedItems = function() {
		var placeholder;
		var html = '';
		var i, length;
		var items = this.selectedItems;

		if (items.length) {
			for (i = 0, length = items.length; i < length; i++) {
				html += this.getItemHtml(items[i]);
			}
		} else {
			placeholder = this.input.data('placeholder');
			html = (placeholder) ? '<div class="placeholder">' + placeholder + '</div>' : '';
		}

		this.trigger.html(html);
	};

	/**
	 * Display choices.
	 *
	 * @param {array} choices
	 */
	Select.prototype.displayChoices = function(choices) {
		var choicesHtml = '',
		    classes,
		    id,
		    key,
		    label,
		    i;

		for (i = 0; i < choices.length; i++) {
			classes = 'choice';

			if (choices[i]._lvl) {
				classes += ' level-' + choices[i]._lvl;
			}

			id = choices[i].uniqueId;
			key = choices[i][this.options.key];
			label = choices[i][this.options.label];
			choicesHtml += '<a data-key="' + key + '" class="' + classes + '">' + label + '</a>';
		}

		this.choicesDiv.find('.choices').html(choicesHtml);

		this.input.trigger('edrSelect.shown', [this]);
	};

	/**
	 * Assign a unique id to each item.
	 *
	 * @param {array} items
	 */
	Select.prototype.assignIdsToItems = function(items) {
		var i;

		for (i = 0; i < items.length; i++) {
			items[i].uniqueId = this.uniqueId;
			this.uniqueId += 1;
		}
	};

	/**
	 * Send AJAX request.
	 */
	Select.prototype.sendRequest = function() {
		var that = this;
		var args = {};

		this.running = true;

		if (this.ajaxRequest) {
			this.ajaxRequest.abort();
		}

		this.currentFilterValue = this.getFilterValue();
		args.input = this.currentFilterValue;
		args = $.extend(args, this.options.ajaxArgs);

		// Send AJAX request.
		this.ajaxRequest = $.ajax({
			type: 'get',
			cache: false,
			dataType: 'json',
			url: this.options.url,
			data: args,
			success: function(response) {
				if (response) {
					that.items = response;
					that.assignIdsToItems(that.items);
					that.displayChoices(that.items);
				}

				that.hideSpinner();
				that.running = false;
			},
			error: function() {
				that.hideSpinner();
				that.running = false;
			}
		});
	},

	/**
	 * Display choices box.
	 */
	Select.prototype.displayChoicesBox = function() {
		var offset;

		if (!this.choicesDiv.is(':visible')) {
			this.clearOtherChoices();

			offset = this.trigger.offset();

			this.choicesDiv.css({
				left: offset.left + 'px',
				top: (offset.top + this.trigger.outerHeight()) + 'px',
				width: (this.trigger.outerWidth() - 2) + 'px',
				display: 'block'
			});

			this.trigger.parent().addClass('edr-select-values_open');
			this.choicesDiv.find('.choices-filter > input').focus();
		}
	};

	/**
	 * Display current choices.
	 */
	Select.prototype.displayCurrentChoices = function() {
		this.running = true;
		this.displayChoicesBox();
		this.displayChoices(this.items);
		this.running = false;
	};

	/**
	 * Display current choices after filtering them.
	 */
	Select.prototype.displayStaticChoices = function() {
		var choices = [];
		var inputValue = this.getFilterValue();
		var searchBy = this.options.searchBy;
		var i, length;

		this.running = true;
		this.displayChoicesBox();

		for (i = 0, length = this.items.length; i < length; i++) {
			if (this.items[i][searchBy].indexOf(inputValue) !== -1) {
				choices.push(this.items[i]);
			}
		}

		this.displayChoices(choices);
		this.running = false;
	};

	/**
	 * Display choices fetched from a server.
	 *
	 * @param {number} timeout in milliseconds
	 */
	Select.prototype.displayDynamicChoices = function(timeout) {
		var that = this;

		this.displayChoicesBox();

		if (this.ajaxTimeout) {
			clearTimeout(this.ajaxTimeout);
			this.ajaxTimeout = null;
		}

		this.showSpinner();

		if (timeout) {
			this.ajaxTimeout = setTimeout(function() {
				that.ajaxTimeout = null;
				that.sendRequest();
			}, timeout);
		} else {
			this.sendRequest();
		}
	};

	/**
	 * Fetch and display choices based on the choices filter.
	 *
	 * @param {boolean} ajaxTimeout
	 */
	Select.prototype.fetchAndDisplayChoices = function(ajaxTimeout) {
		if (this.running || this.disabled) {
			return;
		}

		if (this.currentFilterValue === this.getFilterValue()) {
			this.displayCurrentChoices();
		} else if (!this.options.url) {
			this.displayStaticChoices();
		} else {
			this.displayDynamicChoices(ajaxTimeout);
		}
	};

	/**
	 * Clear choices div.
	 */
	Select.prototype.clearChoices = function() {
		this.trigger.parent().removeClass('edr-select-values_open');
		this.choicesDiv.find('.choices').html('');
		this.choicesDiv.find('> .choices-filter > input').val('');
		this.choicesDiv.css('display', 'none');
	};

	/**
	 * Clear visible choices of other selects.
	 */
	Select.prototype.clearOtherChoices = function() {
		var other = $('input.edr-select-input').not(this.input);

		other.each(function() {
			var select = $(this).data('edrSelect');

			if (select) {
				select.clearChoices();
			}
		});
	};

	/**
	 * Destroy current select object, clear memory used by it.
	 */
	Select.prototype.destroy = function() {
		if (this.ajaxRequest) {
			this.ajaxRequest.abort();
		}

		if (this.ajaxTimeout) {
			clearTimeout(this.ajaxTimeout);
		}

		$('body').off('.edrSelect');
		this.trigger.off();
		this.choicesDiv.remove();
		this.input.data('edrSelect', null);
	};

	/**
	 * Returns a function that initializes a select.
	 *
	 * @param {object} input
	 * @param {object} options
	 */
	return function(input, options) {
		var select = $.data(input, 'edrSelect');

		options = $.extend({
			key:            null,
			label:          null,
			items:          null,
			allowNewValues: false,
			url:            null,
			searchBy:       null,
			ajaxArgs:       {}
		}, options);

		if (!select) {
			select = new Select(input, options);
			$.data(input, 'edrSelect', select);
		}

		return select;
	};
})(jQuery);
