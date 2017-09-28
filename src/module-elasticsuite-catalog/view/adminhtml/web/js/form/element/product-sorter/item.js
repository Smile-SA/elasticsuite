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
    'uiComponent', 
    'jquery',  
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function (Component, $, priceUtil) {

    'use strict';
    
    
    return Component.extend({
        initialize : function () {
            this._super();
            this.observe(['position']);
            this.setPosition(this.data.position);
        },

        setPosition : function (position) {
            if (position) {
                position = parseInt(position, 10);
            }
            
            this.position(position);
        },

        compareTo : function(product) {
            var result = 0;
            result = this.hasPosition() && product.hasPosition() ? this.getPosition() - product.getPosition() : 0;
            result = result === 0 && this.hasPosition() ? -1 : result;
            result = result === 0 && product.hasPosition() ? 1 : result;
            result = result === 0 ? product.getScore() - this.getScore()  : result;
            result = result === 0 ? product.getId() - this.getId(): result;
            
            return result;
        },

        getPosition       : function () { return this.position(); },

        hasPosition       : function () { return this.getPosition() !== undefined && this.getPosition() !== null; },

        getFormattedPrice : function () { return priceUtil.formatPrice(this.data.price, this.data.priceFormat); },

        getId             : function () { return parseInt(this.data.id, 10); },

        getScore          : function () { return parseFloat(this.data.score); },

        getImageUrl       : function () { return this.data.image; },

        getName           : function () { return this.data.name; },
        
        getSku            : function () { return this.data.sku; },

        getIsInStock      : function () { return Boolean(this.data['is_in_stock']) },

        getStockLabel     : function () { return this.getIsInStock() === true ? $.mage.__('In Stock') : $.mage.__('Out Of Stock'); }
    });
    
});
