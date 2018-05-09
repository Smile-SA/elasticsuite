/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['Magento_Ui/js/dynamic-rows/dynamic-rows'], function (DynamicRows) {
    'use strict';

    return DynamicRows.extend({

        defaults: {
            unpinnedPositions: []
        },

        /**
         * Pin/Unpin an item to the top of list.
         */
        togglePinned: function (record) {
            return record.isPinned() ? this.pin(record) : this.unpin(record);
        },

        /**
         * Pin an item on top of the list
         *
         * @param record The record being pinned
         *
         * @returns {exports}
         */
        pin : function(record) {
            this.sortElements();
            record.position = this.getPinnedRecords().length; // Pin the item at the end of already pinned items.

            return this;
        },

        /**
         * Unpin an item. Put it back to his place in the list.
         *
         * @param record The record being pinned
         *
         * @returns {exports}
         */
        unpin : function(record) {
            this.sortElements();
            var pinnedRecords = this.getPinnedRecords();

            // Items which are pinned and should be normally previous the unpinned one, leaving an hole in the list.
            var pinnedBefore = pinnedRecords.filter(function(elem) {
                return this.getUnpinnedPosition(elem) < this.getUnpinnedPosition(record)
            }.bind(this));

            // We finally add +1 to insert after element instead of replacing it.
            record.position = this.getUnpinnedPosition(record) + pinnedRecords.length - pinnedBefore.length + 1;

            this.sortElements();
            return this;
        },

        /**
         * Retrieve all currently pinned records.
         *
         * @returns {*}
         */
        getPinnedRecords : function() {
            return this.elems().filter(function(elem) { return elem.isPinned() === true });
        },

        /**
         * Reapply position correctly according to current order of items.
         * Mandatory to avoid gaps in position field.
         */
        sortElements : function() {
            for (var i = 0; i < this.elems().length; i++) {
                this.elems()[i].position = i + 1;
            }
        },

        /**
         * Initialize children
         *
         * @returns {Object} Chainable.
         */
        initChildren: function () {
            this._super();

            if (this.unpinnedPositions.length === 0) {
                var unpinnedPositions = this.getChildItems().sort(function (itemA, itemB) {
                    if (itemA.default_position !== itemB.default_position) {
                        return parseInt(itemA.default_position, 10) - parseInt(itemB.default_position, 10);
                    }
                    return itemA.attribute_label.localeCompare(itemB.attribute_label);
                });

                for (var i = 0; i < unpinnedPositions.length; i++) {
                    this.unpinnedPositions[unpinnedPositions[i].attribute_id] = i + 1;
                }
            }

            return this;
        },

        /**
         * Retrieve position of an item when not pinned.
         * Unpinned Positions are computed at element first rendering.
         *
         * @param record
         * @returns {Integer}
         */
        getUnpinnedPosition: function (record) {
            return parseInt(this.unpinnedPositions[record.data().attribute_id], 10);
        }
    });
});
