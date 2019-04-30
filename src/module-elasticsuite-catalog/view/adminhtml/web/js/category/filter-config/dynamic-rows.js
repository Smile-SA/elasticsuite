/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['Magento_Ui/js/dynamic-rows/dynamic-rows'], function (DynamicRows) {
    'use strict';

    return DynamicRows.extend({

        /**
         * Set max element position
         *
         * @param {Number} position - element position
         * @param {Object} elem - instance
         */
        setMaxPosition: function (position, elem) {
            if (position || position === 0) {
                this.checkMaxPosition(position);
                // Discard the legacy call to sort() that was here because it was messed up by pinned items.
            } else {
                this.maxPosition += 1;
            }
        },

        sort: function (position, elem) {
            var posCounter = 0;

            var sorted = this.elems().sort(function (propOne, propTwo) {
                var order = 0;

                if (propOne.isPinned() && propTwo.isPinned()) {
                    order = propOne.position - propTwo.position;
                } else if (propOne.isPinned() || propTwo.isPinned()) {
                    order = propOne.isPinned() ? -1 : 1;
                } else {
                    order = propOne.data().default_position - propTwo.data().default_position;

                    if (order === 0) {
                        order = propOne.recordId - propTwo.recordId;
                    }
                }

                return order;
            });

            sorted.forEach(function(record) {
                posCounter++;
                record.position = posCounter;
            });

            this.elems(sorted);

        },

       /**
        * Retrieve all currently pinned records.
        *
        * @returns {*}
        */
        getPinnedRecords : function() {
            return this.elems().filter(
                function(elem) { return elem.isPinned() === true }
            ).sort(function(recordA, recordB) {
                return recordA.position - recordB.position;
            });
        }
    });
});
