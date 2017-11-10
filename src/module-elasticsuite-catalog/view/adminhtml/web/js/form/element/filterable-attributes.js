/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'ko',
    'jquery',
    'Magento_Ui/js/dynamic-rows/dynamic-rows'
], function (ko, $, DynamicRows) {
    'use strict';

    return DynamicRows.extend({

        defaults: {
            isIrrelevantShown          : false,
            irrelevantLabel            : $.mage.__("Some attributes does not match any products in this category and have been hidden."),
            irrelevantButtonLabel      : $.mage.__("Show/Hide irrelevant attributes."),
            irrelevantTooltip          : $.mage.__("Attributes not matching any product of the category actually are hidden for convenience."),
            header                     : $.mage.__("For each attribute of this category, you can define the following display configuration :"),
            autoLabel                  : $.mage.__("Auto"),
            autoDescription            : $.mage.__("Let the engine decide according to attribute coverage rate."),
            alwaysDisplayedLabel       : $.mage.__("Always displayed"),
            alwaysDisplayedDescription : $.mage.__("The filter is always displayed if it has at least one value."),
            alwaysHiddenLabel          : $.mage.__("Always hidden"),
            alwaysHiddenDescription    : $.mage.__("The filter is never displayed even if it has values.")
        },

        /**
         * Calls 'initObservable' of parent
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super().observe(['isIrrelevantShown']);
            return this;
        },

        /**
         * Manage even/odd rows here since we cannot do it with pure CSS : hidden irrelevant attributes causes gaps.
         */
        sort: function () {
            this._super();

            var currentPosition = 0;
            var updatedCollection = this.elems().each(function (record) {
                if (record.data().relevant) {
                    record.data().cssClass = (currentPosition % 2 === 0) ? 'even' : 'odd';
                    currentPosition++;
                }
            });

            this.elems(updatedCollection);
        },

        /**
         * Check if the list contains some attributes that are not relevant.
         *
         * @returns {boolean|*}
         */
        hasIrrelevantAttributes: function () {
            var result = this.elems().some(function (item) {
                return item.data().hasOwnProperty('relevant') && item.data().relevant === false;
            });

            return result;
        },

        /**
         * Toggle the isIrrelevantShown() flag.
         *
         * @returns {exports}
         */
        toggleIrrelevants: function () {
            var currentValue = this.isIrrelevantShown();
            this.isIrrelevantShown(!currentValue);

            return this;
        }
    });
});
