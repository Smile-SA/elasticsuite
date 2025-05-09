/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'Smile_ElasticsuiteCatalog/js/form/element/product-sorter/item',
    'Magento_Ui/js/modal/confirm',
    'ko'
], function (Component, $, Product, confirm, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            showSpinner: true,
            template: "Smile_ElasticsuiteCatalog/form/element/product-sorter",
            refreshFields: {},
            excludedPreviewFields : {},
            maxRefreshInterval: 1000,
            imports: {
                formData: "${ $.provider }:data",
                blacklistedProducts: "${ $.provider }:data.blacklisted_products",
                defaultBlacklistedProducts: "${ $.provider }:data.default.blacklisted_products",
                defaultSortedProducts: "${ $.provider }:data.default.sorted_products"
            },
            links: {
                blacklistedProducts: "${ $.provider }.data.blacklisted_products",
                defaultBlacklistedProducts: "${ $.provider }:data.default.blacklisted_products",
                defaultSortedProducts: "${ $.provider }:data.default.sorted_products"
            },
            messages : {
                emptyText     : $.mage.__('Your product selection is empty.'),
                automaticSort : $.mage.__('Automatic Sort'),
                manualSort    : $.mage.__('Manual Sort'),
                showMore      : $.mage.__('Show more'),
                search        : $.mage.__('Search'),
                searchLabel   : $.mage.__('Refine search'),
                clearSearch   : $.mage.__('Clear search'),
                noResultsText : $.mage.__('Your search returned no results.'),
                previewOnlyModeText  : $.mage.__('Preview Only Mode'),
                resetAllText         : $.mage.__('Clear product positions'),
                resetAllQuestionText : $.mage.__('Clear all products positions and blacklist status ?')
            },
            forceLoading : false,
            allowBlacklist : false,
            allowSearch: false,
            previewOnlyMode : false,
            blacklistedProducts: [],
            defaultSortedProducts: "{}",
            defaultBlacklistedProducts: [],
            storeSortedProducts: "{}",
            storeBlacklistedProducts: [],
            modules: {
                provider: '${ $.provider }'
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
            this.enabled            = this.loadUrl != null;
            this.search             = ko.observable("");
            this.previewOnlyMode    = this.isComponentLinked() && this.isInitialSwitchCopy();

            this.observe(['products', 'countTotalProducts', 'currentSize', 'editPositions', 'loading', 'showSpinner', 'blacklistedProducts', 'previewOnlyMode']);

            this.editPositions.subscribe(function () { this.value(JSON.stringify(this.editPositions())); }.bind(this));

            if (this.forceLoading) {
                this.refreshProductList();
            }
        },

        prepareFormData: function(formData) {
            if (this.excludedPreviewFields) {
                Object.keys(this.excludedPreviewFields).forEach(function (fieldName) {
                    if (formData.hasOwnProperty(fieldName) && formData[fieldName] !== null) {
                        formData[fieldName] = null;
                    }
                });
            }

            return formData;
        },

        updateImports: function (config) {
            if (config.refreshFields) {
                Object.keys(config.refreshFields).each (function (fieldName) {
                    fieldName = '${ $.provider }:data.' + fieldName;

                    if (config.listens === undefined) {
                        config.listens = {}
                    }
                    config.listens[fieldName] = "refreshProductList";
                });
            }
        },

        isComponentLinked: function() {
            return this.scopeSwitcher !== undefined && this.scopeSwitcher !== null;
        },

        isInitialSwitchCopy: function () {
            if (!this.isComponentLinked()) {
                return true;
            }

            if (this.initialSwitchCopy === undefined) {
                this.initialSwitchCopy = parseInt(this.scopeSwitcher, 10) == 0 && this.formData['store_id'] != 0;
            }

            return this.initialSwitchCopy;
        },

        switchScope: function(useStorePositions) {
            if (!this.isComponentLinked()) {
                return;
            }

            if (parseInt(useStorePositions, 10) == 0) {
                // Backup current store level positions and blacklist.
                this.storeSortedProducts = JSON.stringify(this.editPositions());
                this.storeBlacklistedProducts = this.blacklistedProducts().slice(0);
                // Switch positions and blacklist.
                this.editPositions(JSON.parse(this.defaultSortedProducts));
                this.blacklistedProducts(this.defaultBlacklistedProducts.slice(0));
            } else {
                if (this.isInitialSwitchCopy()) {
                    // Copy current (default) positions and blacklist to store level.
                    this.storeSortedProducts = JSON.stringify(this.editPositions());
                    this.storeBlacklistedProducts = this.blacklistedProducts().slice(0);
                    this.initialSwitchCopy = false;
                }
                // Restore store level positions and blacklist.
                this.editPositions(JSON.parse(this.storeSortedProducts));
                this.blacklistedProducts(this.storeBlacklistedProducts.slice(0));
            }

            this.previewOnlyMode(!this.previewOnlyMode());

            this.refreshProductList();
        },

        resetAllProducts: function() {
            confirm({
                content: this.messages.resetAllQuestionText,
                actions: {
                    /**
                     * Confirm action.
                     */
                    confirm: function () {
                        this.editPositions({});

                        this.blacklistedProducts([]);
                        this.provider().data['blacklisted_products'] = this.blacklistedProducts();

                        this.refreshProductList();
                    }.bind(this)
                }
            });

            return false;
        },

        refreshProductList: function () {
            if (this.refreshRateLimiter !== undefined) {
                clearTimeout();
            }

            this.loading(true);

            this.refreshRateLimiter = setTimeout(function () {
                var formData = this.prepareFormData(this.formData);
                Object.keys(this.editPositions()).forEach(function (productId) {
                    formData['product_position[' + productId + ']'] = this.editPositions()[productId];
                }.bind(this));

                formData['page_size'] = this.currentSize();
                formData['search'] = this.search();

                if (this.enabled) {
                    this.loadXhr = $.post(this.loadUrl, this.formData, this.onProductListLoad.bind(this));
                }
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
            if (productData.can_use_manual_sort !== false && this.editPositions()[productData.id]) {
                productData.position = this.editPositions()[productData.id];
            }

            if ($.inArray(parseInt(productData.id, 10), this.blacklistedProducts()) >= 0) {
                productData.is_blacklisted = true;
            }

            return new Product({data : productData});
        },

        hasProducts: function () {
            return this.products().length > 0;
        },

        hasMoreProducts: function () {
            return this.products().length < this.countTotalProducts();
        },

        enterSearch: function (d, e) {
            e.keyCode === 13 && this.refreshProductList();
            return true;
        },

        resetSearch: function () {
            this.search('');
            this.refreshProductList();
        },

        hasSearch: function () {
            return (this.search() !== '');
        },

        searchProducts: function () {
            this.refreshProductList();
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
            this.products().forEach(function (currentProduct) {
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
            $(element).append('<li class="clear"></li>');
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
            if (this.previewOnlyMode()) {
                return;
            }
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
        },

        allowBlacklist: function() {
            return this.allowBlacklist;
        },

        toggleBlackListed: function(product) {
            if (this.previewOnlyMode()) {
                return;
            }
            var state = !product.isBlacklisted();
            product.setIsBlacklisted(state);

            if (state === true) {
                this.blacklistedProducts().push(product.getId());
            }

            if (state === false) {
                var index = this.blacklistedProducts().indexOf(product.getId());
                if (index >= 0) {
                    this.blacklistedProducts().splice(index, 1);
                }
            }

            // Array unique callback.
            var blacklistedProducts = this.blacklistedProducts().filter(function (value, index, self) {
                return self.indexOf(value) === index;
            });

            this.blacklistedProducts(blacklistedProducts);
            this.provider().data['blacklisted_products'] = this.blacklistedProducts();
        }
    });
});
