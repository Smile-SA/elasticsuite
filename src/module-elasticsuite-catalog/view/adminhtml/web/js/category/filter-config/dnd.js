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
 * @copyright 2018 Smile
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
                plannedPosition = depElemPosition + 1;
            } else if (dragData.depElement.insert === 'before') {
                plannedPosition = dragData.instanceCtx.position = depElemPosition;
            }

            if (dragData.instanceCtx.isPinned() === true) {
                var maxPinnedPosition = dragData.instanceCtx.parentComponent().getPinnedRecords().length + 1;
                if (plannedPosition > maxPinnedPosition) {
                    // Do nothing in this case, this occurs when dropping a pinned item outside other pinned items.
                    return;
                }
            }

            dragData.instanceCtx.position = plannedPosition;
            dragData.instanceCtx.parentComponent().sortElements();
        }
    });
});
