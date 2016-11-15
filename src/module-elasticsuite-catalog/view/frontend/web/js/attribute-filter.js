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

        initialize: function () {
            this._super();
            this.originalItems = this.items;
            this.observe(['displayedItems', 'fulltextSearch']);

            var lastSelectedIndex = Math.max.apply(null, (this.originalItems.map(
                function(v, k) {return v.isSelected ? k : 0;}))
            );
            this.maxSize = Math.max(this.maxSize, lastSelectedIndex + 1);

            this.initSearchPlaceholder();
            this.onShowLess();
        },

        initSearchPlaceholder: function () {
            var examples = this.items.slice(0, 2).map(function(item) {return item.label});

            if (this.items.length > 2) {
                examples.push('...');
            }

            this.searchPlaceholder = $.mage.__('Search (%s)').replace('%s', examples.join(', '));
        },
        
        onSearchChange: function () {
            if (this.fulltextSearch().trim() == "") {
                this.items = this.originalItems;
                this.fulltextSearch(null);
                this.onShowLess();
            } else {

            }
        },
        
        onShowMore: function() {
            this.displayedItems(this.originalItems);
        },
        
        onShowLess: function() {
            this.displayedItems(this.originalItems.slice(0, this.maxSize));
        },

        displayShowMore: function() {
            return (this.hasMoreItems || this.displayedItems().length < this.items.length) && this.fulltextSearch() == null;
        },
        
        displayShowLess: function() {
            return this.displayedItems().length > this.maxSize && this.fulltextSearch() == null;
        }
    });
});