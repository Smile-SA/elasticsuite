/*jshint browser:true jquery:true*/
define([
    'ko',
    'jquery',
    'underscore',
    'mage/template',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/lib/ko/template/loader',
    'jquery/ui',
    'mage/translate',
    'mageQuickSearch'
], function (ko, $, _, mageTemplate, priceUtil, templateLoader) {
    'use strict';

    $.widget('smileEs.quickSearch', $.mage.quickSearch, {
        options: {
            autocomplete: 'off',
            minSearchLength: 2,
            responseFieldElements: 'dl dd',
            selectClass: 'selected',
            submitBtn: 'button[type="submit"]',
            searchLabel: '[data-role=minisearch-label]'
        },

        /**
         * Overriden constructor to ensure templates initialization on load
         *
         * @private
         */
        _create: function () {
            this.templateCache = [];
            this._initTemplates();
            this._super();
        },

        /**
         * Init templates used for rendering when instantiating the widget
         *
         * @private
         */
        _initTemplates: function() {
            for (var template in this.options.templates) {
                this._loadTemplate(template);
            }
        },

        /**
         * Load a template for render
         *
         * @param type The type to render
         *
         * @private
         */
        _loadTemplate: function (type) {
            var templateFile = this.options.templates[type]['template'];
            templateLoader.loadTemplate(templateFile).done(function (renderer) {
                this.options.templates[type]['template'] = renderer;
            }.bind(this));
        },

        /**
         * Get rendering template for a given element. Will look into this.options.templates[element.type] for the renderer.
         * Returns an evaluated template for the given element's type.
         *
         * @param element The autocomplete result to display
         *
         * @returns function
         *
         * @private
         */
        _getTemplate: function (element) {
            var source = this.options.template; // Fallback to standard widget template
            var type   = element.type ? element.type : 'undefined';

            if (this.templateCache[type]) {
                return this.templateCache[type];
            }

            if (element.type && this.options.templates && this.options.templates[element.type]) {
                source = this.options.templates[element.type].template;
            }

            this.templateCache[type] = mageTemplate(source);

            return this.templateCache[type];
        },

        /**
         * Render an autocomplete item in the result list
         *
         * @param element The element : an autocomplete result
         * @param index   The element index
         *
         * @returns {*|jQuery|HTMLElement}
         *
         * @private
         */
        _renderItem: function (element, index) {
            var template = this._getTemplate(element);
            element.index = index;

            if (element.price) {
                element.price = priceUtil.formatPrice(element.price, this.options.priceFormat);
            }

            return template({
                data: element
            });
        },

        /**
         * Return the wrapper for all autocomplete results
         *
         * @returns {*|jQuery|HTMLElement}
         *
         * @private
         */
        _getResultWrapper: function () {
            return $('<div class="smile-elasticsuite-autocomplete-result"></div>');
        },

        /**
         * Return the header for an autocomplete result section
         *
         * @param type The type of element to display
         *
         * @returns {*|jQuery|HTMLElement}
         *
         * @private
         */
        _getSectionHeader: function(type) {
            var title = '';
            var header = $('<dl role="listbox" class="autocomplete-list"></dl>');

            if (type !== undefined) {
                title = this._getSectionTitle(type);
                header.append(title);
            }

            return header;
        },

        /**
         * Return the title for an autocomplete result section
         *
         * @param type
         *
         * @returns {*|jQuery|HTMLElement}
         *
         * @private
         */
        _getSectionTitle: function(type) {
            var title = '';
            if (this.options.templates && this.options.templates[type].title) {
                title = $('<dt role="listbox" class="autocomplete-list-title title-' + type + '">' + this.options.templates[type].title + '</dt>');
            }

            return title;
        },

        /**
         * Check wether the incoming string is not empty or if doesn't consist of spaces.
         *
         * @param {String} value - Value to check.
         *
         * @returns {Boolean}
         *
         * @private
         */
        _isEmpty : function(value) {
            return value === null || value.trim().length === 0;
        },

        /**
         * Executes when the value of the search input field changes. Executes a GET request
         * to populate a suggestion list based on entered text. Handles click (select), hover,
         * and mouseout events on the populated suggestion list dropdown.
         *
         * Overriden to :
         *  - move rendering of elements in a subfunction.
         *  - manage redirection when clicking a result having an href attribute.
         *
         * @private
         */
        _onPropertyChange: function () {
            var searchField = this.element,
                clonePosition = {
                    position: 'absolute',
                    // Removed to fix display issues
                    // left: searchField.offset().left,
                    // top: searchField.offset().top + searchField.outerHeight(),
                    width: searchField.outerWidth()
                },
                value = this.element.val();

            this.submitBtn.disabled = this._isEmpty(value);

            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                $.get(this.options.url, {q: value}, $.proxy(function (data) {
                    var self = this;
                    var lastElement = false;
                    var content = this._getResultWrapper();
                    var sectionDropdown = this._getSectionHeader();
                    $.each(data, function(index, element) {

                        if (!lastElement || (lastElement && lastElement.type != element.type)) {
                            sectionDropdown = this._getSectionHeader(element.type);
                        }

                        var elementHtml = this._renderItem(element, index);

                        sectionDropdown.append(elementHtml);

                        if (!lastElement || (lastElement && lastElement.type != element.type)) {
                            content.append(sectionDropdown);
                        }

                        lastElement = element;
                    }.bind(this));
                    this.responseList.indexList = this.autoComplete.html(content)
                        .css(clonePosition)
                        .show()
                        .find(this.options.responseFieldElements + ':visible');

                    this._resetResponseList(false);
                    this.element.removeAttr('aria-activedescendant');

                    if (this.responseList.indexList.length) {
                        this._updateAriaHasPopup(true);
                    } else {
                        this._updateAriaHasPopup(false);
                    }

                    this.responseList.indexList
                        .on('click', function (e) {
                            self.responseList.selected = $(this);
                            if (self.responseList.selected.attr("href")) {
                                window.location.href = self.responseList.selected.attr("href");
                                e.stopPropagation();
                                return false;
                            }
                            self.searchForm.trigger('submit');
                        })
                        .on('mouseenter mouseleave', function (e) {
                            self.responseList.indexList.removeClass(self.options.selectClass);
                            $(this).addClass(self.options.selectClass);
                            self.responseList.selected = $(e.target);
                            self.element.attr('aria-activedescendant', $(e.target).attr('id'));
                        })
                        .on('mouseout', function () {
                            if (!self._getLastElement() && self._getLastElement().hasClass(self.options.selectClass)) {
                                $(this).removeClass(self.options.selectClass);
                                self._resetResponseList(false);
                            }
                        });
                }, this));
            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        }
    });

    return $.smileEs.quickSearch;
});
