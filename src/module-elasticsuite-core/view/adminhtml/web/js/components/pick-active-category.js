/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Catalog/js/components/new-category'
], function (Category) {
    'use strict';

    return Category.extend({

        /**
         * Check label decoration
         *
         * @param {Object} data - selected option data
         * @returns {boolean}
         */
        isLabelDecoration: function (data) {
            return (
                (data.hasOwnProperty(this.separator) && this.labelsDecoration) ||
                (data.hasOwnProperty('is_active') &&
                    ((data.is_active === false) || (parseInt(data.is_active, 10) === 0)))
            );
        },


        /**
         * Toggle activity list element
         *
         * @param {Object} data - selected option data
         * @returns {Object} Chainable
         */
        toggleOptionSelected: function (data) {
            if (data.hasOwnProperty('is_active') &&
                ((data.is_active === false) || (parseInt(data.is_active, 10) === 0))
            ) {
                return this;
            }

            return this._super(data);
        }
    });
});
