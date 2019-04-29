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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
define(['Magento_Ui/js/dynamic-rows/dnd'], function (Dnd) {
    'use strict';

    return Dnd.extend({

        /**
         * Set position to element.
         * Overridden to prevent dropping pinned item anywhere in the table.
         *
         * @param {Object} depElem - dep element
         * @param {Object} depElementCtx - dep element context
         * @param {Object} dragData - data draggable element
         */
        setPosition: function (depElem, depElementCtx, dragData) {
            var depElemPosition = ~~depElementCtx.position;
            var plannedPosition;

            if (dragData.depElement.insert === 'after') {
                // hack : ensure sort() call on position will properly compute this item after the other, and before the next.
                plannedPosition = depElemPosition + 0.5 ;
            } else if (dragData.depElement.insert === 'before') {
                plannedPosition = depElemPosition - 1;
            }

            if (dragData.instanceCtx.isPinned() === true) {
                var pinnedRecords = dragData.instanceCtx.parentComponent().getPinnedRecords();
                var lastPinnedRecord = pinnedRecords[pinnedRecords.length - 1];
                if (plannedPosition > lastPinnedRecord.position) {
                    plannedPosition = lastPinnedRecord.position + 1;
                }
            }

            dragData.instanceCtx.position = plannedPosition;
            dragData.instanceCtx.parentComponent().sort(dragData.instanceCtx.position, dragData.instanceCtx);
        }
    });
});
