/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'uiComponent',
    'jquery',
    'Magento_Catalog/js/price-utils',
    'Smile_ElasticsuiteCatalog/js/form/element/product-sorter/item',
    'mage/translate'
], function (Component, $, priceUtil, Item) {

    'use strict';

    return Item.extend({
        getEffectClass : function () {
            if (this.data.effect === -1) {
                return 'down';
            } else if (this.data.effect === 1) {
                return 'up';
            }

            return '';
        },

        getScoreLabel: function() {
            return $.mage.__("Score : ");
        }
    });

});
