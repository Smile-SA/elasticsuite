/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

/*jshint browser:true jquery:true*/
/*global console*/


define(["uiComponent", "jquery"], function (Component, $) {

    "use strict";
    
    var Product = Component.extend({
        initialize : function () {
            this._super();
            this.observe(['position']);
        },
        
        getRowCssClass : function (rowPosition) {
            var cssClasses = [(rowPosition() % 2) === 0 ? "even" : "odd"];
            return cssClasses.join(' ');
        },
        
        hasPosition: function () {
            return this.position() !== undefined && this.position() !== null;
        }
    });
    
    var FormListener = function (formId, formChangedEvent, listenFormElements) {
        this.form               = $('#' + formId);
        this.formChangedEvent   = formChangedEvent;
        this.listenFormElements = listenFormElements;
    };
    
    FormListener.prototype.startListener = function () {
        if (this.timer === undefined || this.timer === null) {
            this.hash  = this.getFormHash();
            this.timer = setInterval(this.detectChanges.bind(this), 1000);
        }
    };
    
    FormListener.prototype.stopListener = function () {
        if (this.timer === undefined || this.timer === null) {
            clearInterval(this.timer);
            delete this.timer; 
        }
    };
    
    FormListener.prototype.getFormHash = function () {
        var serializedElements = this.form.serializeArray();
        
        var filterElementFunction = this.getFilterFunction();
        if (filterElementFunction) {
            serializedElements = serializedElements.filter(filterElementFunction);
        }
        
        return serializedElements.map(function (formElement) { return formElement.name + formElement.value; }).join('|');
    }
    
    FormListener.prototype.getFilterFunction = function () {
        var filterFunction = null;
        
        var isElementTargeted = function (elementName, targetName) {
            var isElementTargeted = elementName === targetName;
            if (targetName.match(/.*\[.*\]$/)) {
                isElementTargeted = elementName.startsWith(targetName);
            }
            return isElementTargeted;
        }
        
        if (Object.prototype.toString.call(this.listenFormElements) === '[object Array]') {
            filterFunction = function(element) {
                var addElement = false;
                for (var i = 0; i < this.listenFormElements.length; i++) {
                    addElement = addElement || isElementTargeted(element.name, this.listenFormElements[i]);
                }
                return addElement;
            };
        } else if (typeof this.listenFormElements === 'string') {
            filterFunction = function (element) {
                return isElementTargeted(element.name, this.listenFormElements);
            };
        } else if (typeof this.listenFormElements === 'object') {
            filterFunction = function (element) {
                return this.listenFormElements.name === element.name;
            };
        }
        
        return filterFunction.bind(this);
    };
    
    FormListener.prototype.detectChanges = function () {
        var currentHash = this.getFormHash();
        
        if (currentHash !== this.hash) {
            $(document).trigger(this.formChangedEvent, [this.form]);
        }
        
        this.hash = currentHash;
    }
    
    FormListener.prototype.serialize = function () {
        return this.form.serialize();
    }
    
    var productSorterComponent = Component.extend({
        
        initialize : function () {
            this._super();
            
            this.products     = [];
            
            this.observe(['products']);
            this.addListners();
            this.loadProducts();
        },
        
        addListners : function () {
            var formListenerChangeEvent = 'formListener:' + this.targetElementName;
            this.formListener = new FormListener(this.formId, formListenerChangeEvent, this.refreshElements);
            $(document).bind(formListenerChangeEvent, this.loadProducts.bind(this));
        },
        
        loadProducts : function () {
            this.loadXhr = $.post(this.loadUrl, this.formListener.serialize(), this.onProductLoad.bind(this));
        },
        
        onProductLoad : function (loadedData) {
            
            var loadedProducts = loadedData['products'].map(
                function (productData) { return new Product(productData); }
            );

            this.formListener.startListener();

            this.products(loadedProducts);
        },
        
        getSortedProducts : function () {
            var products = this.products()
            products.sort(function (product1, product2) {
                if (product1.hasPosition() && product2.hasPosition()) {
                    return product1.position() - product2.position();
                } else if (product1.hasPosition()) {
                    return -1;
                } else if (product2.hasPosition()) {
                    return 1;
                } else {
                    return product1.score - product2.score;
                }
            });

            return products;
        },
        
        enableSortableList: function (element, component) {
            $(element).sortable({
                helper : "clone",
                handle : ".draggable-handle",
                placeholder: "product-list-item-placeholder"
            });
            $(element).disableSelection();
        },
        
        getSerializedSortOrder: function () {
            var serializedProductPosition = {};
            
            this.products()
                .filter(function (product) {
                    return product.hasPosition();
                })
                .each(function (product) {
                    serializedProductPosition[product.id] = product.position();
                });
            
            return JSON.stringify(serializedProductPosition);
        }
    }); 
    
    return productSorterComponent;
});