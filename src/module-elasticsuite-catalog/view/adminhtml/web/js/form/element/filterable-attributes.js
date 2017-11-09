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
    'Magento_Ui/js/dynamic-rows/dynamic-rows'
], function (ko, DynamicRows) {
    'use strict';

    return DynamicRows.extend({

        defaults: {
            isIrrelevantShown : false,
            irrelevantLabel : "Some attributes does not match any products in this category and have been hidden.",
            irrelevantButtonLabel : "Show/Hide irrelevant attributes.",
            irrelevantTooltip : "Attributes not matching any product of the category actually are hidden for convenience."
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
         * Check if the list contains some attributes that are not relevant.
         *
         * @returns {boolean|*}
         */
        hasIrrelevantAttributes : function() {
            var result = this.elems().some(function (item) {
                return item.data().hasOwnProperty('relevant') && item.data().relevant === false;
            });

            return result;
        },

        /**
         * Get the text to display when the list contains irrelevant attributes.
         *
         * @returns {*}
         */
        getIrrelevantLabel : function () {
            return this.irrelevantLabel;
        },

        /**
         * Get the button label to display when the list contains irrelevant attributes.
         *
         * @returns {*}
         */
        getIrrelevantButtonLabel : function() {
            return this.irrelevantButtonLabel;
        },

        /**
         * Get the button label to display when the list contains irrelevant attributes.
         *
         * @returns {*}
         */
        getIrrelevantTooltip : function() {
            return this.irrelevantTooltip;
        },

        /**
         * Toggle the isIrrelevantShown() flag.
         *
         * @returns {exports}
         */
        toggleIrrelevants : function() {
            var currentValue = this.isIrrelevantShown();
            this.isIrrelevantShown(!currentValue);

            return this;
        }
    });
});
