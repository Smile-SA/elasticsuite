/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'uiComponent',
    'underscore',
    'mage/translate'
], function ($, Component, _) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Smile_ElasticsuiteCatalog/attribute-filter",
            showMoreLabel       : $.mage.__("Show more"),
            showLessLabel       : $.mage.__("Show less"),
            noSearchResultLabel : $.mage.__("No value matching the search <b>%s</b>.")
        },

        /**
         * Component initialization
         */
        initialize: function () {
            this._super();
            this.expanded = false;
            this.items = this.items.map(this.addItemId.bind(this));
            this.observe(['fulltextSearch', 'expanded']);

            var lastSelectedIndex = Math.max.apply(null, (this.items.map(
                function (v, k) {return v['is_selected'] ? k : 0;}))
            );
            this.maxSize = Math.max(this.maxSize, lastSelectedIndex + 1);

            this.initSearchPlaceholder();
            this.onShowLess();
            this.displaySearch = this.displayShowMore();
          
        },

        /**
         * Init the place holder
         */
        initSearchPlaceholder: function () {
            var examples = this.items.slice(0, 2).map(function (item) {return item.label});

            if (this.items.length > 2) {
                examples.push('...');
            }
            this.searchPlaceholder = $('<div/>').html($.mage.__('Search (%s)').replace('%s', examples.join(', '))).text();
        },

        /**
         * Triggered when typing on the search input
         */
        onSearchChange: function (component, ev) {
            var text = ev.target.value;
            if (text.trim() === "") {
                component.fulltextSearch(null);
                component.onShowLess();
            } else {
                component.fulltextSearch(text);
                component.onShowMore();
            }
            return true;
        },
        
        /**
         * Triggered when leaving the search field.
         */
        onSearchFocusOut: function(component, ev) {
            var text = ev.target.value;
            if (text.trim() === "") {
                component.fulltextSearch(null);
                ev.target.value = "";
            }
        },

        /**
         * Retrieve additional Results
         *
         * @param callback
         */
        loadAdditionalItems: function (callback) {
            $.get(this.ajaxLoadUrl, function (data) {
                this.items = data.map(this.addItemId.bind(this));
                this.hasMoreItems  = false;
                
                if (callback) {
                    return callback();
                }
            }.bind(this));
        },

        /**
         * Retrieve items to display
         *
         * @returns {*}
         */
        getDisplayedItems: function () {
            var items = this.items;
            
            if (this.expanded() === false) {
                items = this.items.slice(0, this.maxSize);
            }
            
            if (this.fulltextSearch()) {
               var searchTokens    = this.slugify(this.fulltextSearch()).split('-');
               var lastSearchToken = searchTokens.splice(-1, 1)[0];

               items = items.filter(function(item) {
                   var isValidItem = true;
                   var itemTokens = this.slugify(item.label).split('-');
                   searchTokens.forEach(function(currentToken) {
                       if (itemTokens.indexOf(currentToken) === -1) {
                           isValidItem = false;
                       }
                   })
                   if (isValidItem && lastSearchToken) {
                       var ngrams = itemTokens.map(function(token) {return token.substring(0, lastSearchToken.length)});
                       isValidItem = ngrams.indexOf(lastSearchToken) !== -1;
                   }
                   return isValidItem;
               }.bind(this))
            }

            return items;
        },

        /**
         * Does the search have a result
         */
        hasSearchResult: function () {
            return this.getDisplayedItems().length > 0
        },
        
        /**
         * Search result message
         */
        getSearchResultMessage : function() {
            const escapedSearch = $('<div/>').text(this.fulltextSearch()).html();
            return this.noSearchResultLabel.replace("%s", `"${escapedSearch}"`);
        },
        
        /**
         * Callback for the "Show more" button
         */
        onShowMore: function () {
            if (this.hasMoreItems) {
                this.loadAdditionalItems(this.onShowMore.bind(this));
            } else {
                this.expanded(true);
            }
        },

        /**
         * Index the text to be searched.
         */
        slugify: function(text) {
          return text.toString().toLowerCase()
            .replace(/\s+/g, '-')                                              // Replace spaces with -
            .replace(/[^\w\u0400-\u052F\u2DE0-\u2DFF\uA640-\uA69F'\-]+/g, '')  // Remove all non-word chars
            .replace(/\-\-+/g, '-')                                            // Replace multiple - with single -
            .replace(/^-+/, '')                                                // Trim - from start of text
        },
        
        /**
         * Callback for the "Show less" button
         */
        onShowLess: function () {
            this.expanded(false);
        },

        /**
         * Check if the filter can be expanded
         *
         * @returns {boolean}
         */
        enableExpansion : function () {
           return this.hasMoreItems || this.items.length > this.maxSize;
        },

        /**
         * Displays the "Show More" link
         *
         * @returns {*|boolean}
         */
        displayShowMore: function () {
            return this.enableExpansion() && this.expanded() === false && !this.fulltextSearch();
        },

        /**
         * Displays the "Show Less" link
         *
         * @returns {*|boolean}
         */
        displayShowLess: function () {
            return this.enableExpansion() && this.expanded() === true && !this.fulltextSearch();
        },

        /**
         * Add an id to items.
         */
        addItemId: function (item) {
            item.id = _.uniqueId(this.index + "_option_");
            item.displayProductCount = this.displayProductCount && (item.count >= 1);

            return item;
        },
    });
});
