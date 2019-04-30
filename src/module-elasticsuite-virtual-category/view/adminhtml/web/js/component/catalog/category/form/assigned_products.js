/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Ui/js/form/components/html',
    'underscore',
    'MutationObserver'
], function (Component, _) {
    'use strict';

    return Component.extend({
        defaults: {
            formField: "in_category_products",
            links: {
                addedProducts: '${ $.provider }:${ $.dataScope }.added_products',
                deletedProducts: '${ $.provider }:${ $.dataScope }.deleted_products'
            }
        },
        initialize: function () {
            this._super();
            this.initAssignedProductsListener();
        },
        
        initObservable: function () {
            this._super();
            this.addedProducts   = {};
            this.deletedProducts = {};
            this.observe('addedProducts');
            this.observe('deletedProducts');
            
            return this;
        },
        
        initAssignedProductsListener: function () {
            var observer = new MutationObserver(function () {
                var selectedProductsField = document.getElementById(this.formField);
                if (selectedProductsField) {
                    observer.disconnect();
                    observer = new MutationObserver(this.onProductIdsUpdated.bind(this));
                    observerConfig = {attributes: true, attributeFilter: ['value']};
                    observer.observe(selectedProductsField, observerConfig);
                }
            }.bind(this));
            
            var observerConfig = {childList: true, subtree: true};
            observer.observe(document, observerConfig);
        },
        
        onProductIdsUpdated: function (mutations) {
            while (mutations.length > 0) {
                var currentMutation = mutations.shift();
                var productIds = Object.keys(JSON.parse(currentMutation.target.value));
                this.updateProductIds(productIds);
            }
        },
        
        updateProductIds: function (productIds) {
            if (this.initialProductIds === undefined) {
                this.initialProductIds = productIds;
            } else {
                this.addedProducts(_.difference(productIds, this.initialProductIds));
                this.deletedProducts(_.difference(this.initialProductIds, productIds));
            }
        }
    })
});
