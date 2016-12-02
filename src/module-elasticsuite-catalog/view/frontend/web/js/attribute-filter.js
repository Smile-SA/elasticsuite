/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'uiComponent',
    'mage/translate'
], function ($, Component) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Smile_ElasticsuiteCatalog/attribute-filter",
            showMoreLabel: $.mage.__("Show more"),
            showLessLabel: $.mage.__("Show less")
        },

        /**
         * Component initialization
         */
        initialize: function () {
            this._super();
            this.expanded = false;
            
            this.observe(['fulltextSearch', 'expanded']);

            var lastSelectedIndex = Math.max.apply(null, (this.items.map(
                function (v, k) {return v['is_selected'] ? k : 0;}))
            );
            this.maxSize = Math.max(this.maxSize, lastSelectedIndex + 1);

            this.initSearchPlaceholder();
            this.onShowLess();
        },

        /**
         * Init the place holder
         */
        initSearchPlaceholder: function () {
            var examples = this.items.slice(0, 2).map(function (item) {return item.label});

            if (this.items.length > 2) {
                examples.push('...');
            }

            this.searchPlaceholder = $.mage.__('Search (%s)').replace('%s', examples.join(', '));
        },

        /**
         * Triggered when typing on the search input
         */
        onSearchChange: function () {
            if (this.fulltextSearch().trim() === "") {
                this.fulltextSearch(null);
                this.onShowLess();
            }
        },

        /**
         * Retrieve additional Results
         *
         * @param callback
         */
        loadAdditionalItems: function (callback) {
            $.get(this.ajaxLoadUrl, function (data) {
                this.items = data;
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
            
            return items;
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
        }
    });
});
