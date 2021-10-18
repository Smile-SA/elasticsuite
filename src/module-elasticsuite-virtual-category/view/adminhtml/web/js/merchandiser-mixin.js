define(
    ['jquery'],
    function ($) {

        'use strict';

        return function (targetWidget) {
            $.widget('mage.visualMerchandiser', targetWidget, {
                /**
                 * Don't run smart category setup if block has been removed
                 *
                 * @returns void
                 */
                setupSmartCategory: function () {
                    if ($('#smart_category_rules').val() !== undefined) {
                        this._super();
                    }
                },

                /**
                 * Don't run tile view setup if block has been removed
                 *
                 * @returns void
                 */
                setupTileView: function () {
                    // Disable tile view
                },

                /**
                 * Remove product by refresing the grids
                 * Fix find entity_id because position column has been removed
                 *
                 * @param {Object} row
                 */
                removeRow: function (row) {
                    var data = this.getSortData();
                    data.unset(parseInt(row.find('.col-entity_id').text()));
                    $('#vm_category_products').val(Object.toJSON(data));
                    this.savePositionCache(function () {
                        this.reloadViews();
                    }.bind(this));
                },
            });

            return $.mage.visualMerchandiser;
        };
    }
);
