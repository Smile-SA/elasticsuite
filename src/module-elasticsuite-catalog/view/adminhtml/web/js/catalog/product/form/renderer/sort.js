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

/*jshint browser:true jquery:true*/
/*global console*/

define([
    'uiComponent', 
    'jquery', 
    'Smile.ES.FormListener', 
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function (Component, $, FormListener, priceUtil) {

    'use strict';

    var Product = Component.extend({
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

        getIsInStock      : function () { return Boolean(this.data['is_in_stock']) },

        getStockLabel     : function () { return this.getIsInStock() === true ? $.mage.__('In Stock') : $.mage.__('Out Of Stock'); }
    });

    var productSorterComponent = Component.extend({

        initialize : function () {
            this._super();

            this.products           = [];
            this.countTotalProducts = 0;
            this.currentSize        = this.pageSize;

            this.addListners();
            this.observe(['products', 'countTotalProducts', 'currentSize']);
            this.loadProducts();
        },

        addListners : function () {
            // Reload the product list when something change into the form.
            var formListenerChangeEvent = 'formListener:' + this.targetElementName;
            this.formListener = new FormListener(this.formId, formListenerChangeEvent, this.refreshElements);
            $(document).bind(formListenerChangeEvent, this.loadProducts.bind(this));
        },

        loadProducts : function () {
            if (this.loadXhr) {
                this.loadXhr.abort();
            }
            this.loadXhr = $.post(this.loadUrl, this.getLoadParams(), this.onProductLoad.bind(this));
        },

        getLoadParams : function() {
            var formData = this.formListener.serializeArray();

            if (Array.isArray(this.savedPositions)) {
                this.savedPositions = {};
            }

            var positionedProducts = this.isLoaded ? this.getEditPositions() : this.savedPositions;
            
            Object.keys(positionedProducts).each(function(productId) {
                formData.push({name: 'product_position[' + productId + ']', value: positionedProducts[productId]});
            });

            formData.push({name: 'page_size', value: this.currentSize()});

            return formData;
        },

        onProductLoad : function (loadedData) {
            this.isLoaded = true;
            this.products(loadedData.products.map(this.createProduct.bind(this)));
            this.countTotalProducts(parseInt(loadedData.size, 10));
            this.currentSize(Math.max(this.currentSize(), this.products().length));
            this.formListener.startListener();
            
        },

        createProduct : function(productData) {
            productData.priceFormat = this.priceFormat;
            
            if (this.products() !== undefined && this.getEditPositions()[productData.id]) {
                productData.position = this.getEditPositions()[productData.id];
            } else if (this.savedPositions[productData.id]) {
                productData.position = this.savedPositions[productData.id];
            }

            return new Product({data : productData});
        },

        getSortedProducts : function () {
            var products = this.products();
            products.sort(function (product1, product2) { return product1.compareTo(product2); });
            return products;
        },

        getSerializedSortOrder: function () {
            return JSON.stringify(this.getEditPositions());
        },

        getEditPositions : function() {
            var serializedProductPosition = {};

            this.products()
                .filter(function (product) {
                    return product.hasPosition();
                })
                .each(function (product) {
                    serializedProductPosition[product.getId()] = product.getPosition();
                });
            return serializedProductPosition;
        },

        hasProducts: function() {
            return this.products().length > 0;
        },

        hasMoreProducts: function() {
            return this.products().length < this.countTotalProducts();
        },

        showMoreProducts: function()
        {
            this.currentSize(this.currentSize() + this.pageSize);
            this.loadProducts();
        },

        getProductById : function (productId) {
            var product = null;
            productId   = parseInt(productId, 10);
            this.products().each(function(currentProduct) {
                if (currentProduct.getId() === productId) {
                    product = currentProduct;
                }
            });
            return product;
        },

        enableSortableList: function (element, component) {
            $(element).sortable({
                items       : "li:not('.manual-sorting')",
                helper      : 'clone',
                handle      : '.draggable-handle',
                placeholder : 'product-list-item-placeholder',
                update      : component.onSortUpdate.bind(component)
            });
            $(element).disableSelection();
        },

        onSortUpdate : function(event, ui)
        {
            var productId = ui.item.attr('data-product-id');
            var position  = 1;

            var previousProductId = ui.item.prev('li.product-list-item').attr('data-product-id');
            if (previousProductId !== undefined) {
                var previousProduct = this.getProductById(previousProductId);
                position = parseInt(previousProduct.getPosition(), 10) + 1;
            }

            this.getProductById(productId).setPosition(position);

            ui.item.nextAll('li.product-list-item').each(function (index, element) {
                var currentProduct = this.getProductById(element.getAttribute('data-product-id'));
                if(currentProduct.getPosition()) {
                    position = position + 1;
                    currentProduct.setPosition(position);
                }
            }.bind(this))
        },

        toggleSortType: function(product) {
            if (product.getPosition() !== undefined) {
                var lastProduct = this.getSortedProducts()[this.products().length -1];
                if (lastProduct.hasPosition() || lastProduct.getScore() >= product.getScore()) {
                    this.loadProducts();
                }
                product.setPosition(undefined);
                if (this.savedPositions[product.getId()]) {
                    delete this.savedPositions[product.getId()]
                }
            } else {
                var allPositions = this.products()
                    .filter(function (product) { return product.hasPosition(); })
                    .map(function (product) { return product.getPosition(); })
                    .concat([0]);

                var maxPosition  = Math.max.apply(null, allPositions);

                product.setPosition(maxPosition + 1);
            }
        },
        
        getAutomaticSortLabel : function () { 
            return $.mage.__('Automatic Sort'); 
        },

        getManualSortLabel : function () { 
            return $.mage.__('Manual Sort'); 
        },

        getShowMoreLabel : function () {
            return $.mage.__('Show more');
        },
        
        getEmptyListMessage : function() {
            return $.mage.__('Your product selection is empty.');
        }
    }); 

    return productSorterComponent;
});