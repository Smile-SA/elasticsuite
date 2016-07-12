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
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'Smile_ElasticsuiteCatalog/js/form/element/product-sorter/item',
    'MutationObserver'
], function (Component, $, Product) {
    'use strict';

    return Component.extend({
        defaults: {
            showSpinner: true,
            template: "Smile_ElasticsuiteCatalog/form/element/product-sorter",
            refreshFields: {},
            maxRefreshInterval: 1000,
            imports: {
                formData: "${ $.provider }:data"
            },
            messages : {
                blockTitle    : $.mage.__('Product Preview and Sorting'),
                emptyText     : $.mage.__('Your product selection is empty.'),
                automaticSort : $.mage.__('Automatic Sort'),
                manualSort    : $.mage.__('Manual Sort'),
                showMore      : $.mage.__('Show more')
            }
        },
        
        initialize: function ()
        {
            this.updateImports(arguments[0]);
            this._super();
            
            this.editPositions      = JSON.parse(this.value());
            this.products           = [];
            this.countTotalProducts = 0;
            this.pageSize           = parseInt(this.pageSize, 10);
            this.currentSize        = this.pageSize;
            
            this.observe(['products', 'countTotalProducts', 'currentSize', 'editPositions', 'loading', 'showSpinner']);
            
            this.editPositions.subscribe(function () { this.value(JSON.stringify(this.editPositions())); }.bind(this));
        },
        
        updateImports: function (config) {
            Object.keys(config.refreshFields).each (function (fieldName) {
                fieldName = '${ $.provider }:data.' + fieldName;
                
                if (config.listens === undefined) {
                    config.listens = {}
                }
                config.listens[fieldName] = "refreshProductList";
            });
        },
        
        refreshProductList: function () {
            if (this.refreshRateLimiter !== undefined) {
                clearTimeout();
            }

            this.loading(true);

            this.refreshRateLimiter = setTimeout(function () {
                var formData = this.formData;
                Object.keys(this.editPositions()).each(function (productId) {
                    formData['product_position[' + productId + ']'] = this.editPositions()[productId];
                }.bind(this));
                
                formData['page_size'] = this.currentSize();
                this.loadXhr = $.post(this.loadUrl, this.formData, this.onProductListLoad.bind(this));
            }.bind(this), this.maxRefreshInterval);
        },
        
        onProductListLoad: function (loadedData) {
            var products = this.sortProduct(loadedData.products.map(this.createProduct.bind(this)));
            this.products(products);
            this.countTotalProducts(parseInt(loadedData.size, 10));
            this.currentSize(Math.max(this.currentSize(), this.products().length));
            
            var productIds = products.map(function (product) { return product.getId() });
            var editPositions = this.editPositions();
            
            for (var productId in editPositions) {
                if ($.inArray(parseInt(productId, 10), productIds) < 0) {
                    delete editPositions[productId];
                } 
            }
            
            this.editPositions(editPositions);
            this.loading(false);
        },
        
        createProduct: function (productData) {
            productData.priceFormat = this.priceFormat;
            if (this.editPositions()[productData.id]) {
                productData.position = this.editPositions()[productData.id];
            }
            return new Product({data : productData});
        },
        
        hasProducts: function () {
            return this.products().length > 0;
        },

        hasMoreProducts: function () {
            return this.products().length < this.countTotalProducts();
        },

        showMoreProducts: function () {
            this.currentSize(this.currentSize() + this.pageSize);
            this.refreshProductList();
        },
        
        sortProduct : function (products) {
            products.sort(function (product1, product2) { return product1.compareTo(product2); });
            return products;
        },

        getProductById : function (productId) {
            var product = null;
            productId   = parseInt(productId, 10);
            this.products().each(function (currentProduct) {
                if (currentProduct.getId() === productId) {
                    product = currentProduct;
                }
            });
            return product;
        },

        enableSortableList: function (element, component) {
            $(element).sortable({
                items       : "li:not(.automatic-sorting)",
                helper      : 'clone',
                handle      : '.draggable-handle',
                placeholder : 'product-list-item-placeholder',
                update      : component.onSortUpdate.bind(component)
            });
            $(element).disableSelection();
        },

        onSortUpdate : function (event, ui)
        {
            var productId     = ui.item.attr('data-product-id');
            var position      = 1;
            var products      = this.products();
            var editPositions = this.editPositions();

            var previousProductId = ui.item.prev('li.product-list-item').attr('data-product-id');
            if (previousProductId !== undefined) {
                var previousProduct = this.getProductById(previousProductId);
                position = parseInt(previousProduct.getPosition(), 10) + 1;
            }

            this.getProductById(productId).setPosition(position);
            editPositions[productId] = position;

            ui.item.nextAll('li.product-list-item').each(function (index, element) {
                var currentProduct = this.getProductById(element.getAttribute('data-product-id'));
                if(currentProduct.getPosition()) {
                    position = position + 1;
                    currentProduct.setPosition(position);
                    editPositions[currentProduct.getId()] = position;
                }
            }.bind(this))
            
            this.products(this.sortProduct(products));
            this.editPositions(editPositions);
        },
        
        toggleSortType: function (product) {
            var products      = this.products();
            var editPositions = this.editPositions();
            
            if (product.getPosition() !== undefined) {
                var lastProduct = products[products.length -1];
                if (lastProduct.hasPosition() || lastProduct.getScore() >= product.getScore()) {
                    this.refreshProductList();
                }
                product.setPosition(undefined);
                if (editPositions[product.getId()]) {
                    delete editPositions[product.getId()];
                }
            } else {
                var allPositions = products
                    .filter(function (product) { return product.hasPosition(); })
                    .map(function (product) { return product.getPosition(); })
                    .concat([0]);

                var maxPosition   = Math.max.apply(null, allPositions);
                editPositions[product.getId()] = maxPosition + 1;
                product.setPosition(maxPosition + 1);
            }
            
            this.products(this.sortProduct(products));
            this.editPositions(editPositions);
        }
    });
});
