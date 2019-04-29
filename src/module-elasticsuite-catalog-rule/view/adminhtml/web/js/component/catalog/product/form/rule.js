/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Ui/js/form/components/html',
    'jquery',
    'MutationObserver'
], function (Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
            value: {},
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            },
            additionalClasses: "admin__fieldset virtual-rule-fieldset"
        },
        initialize: function () {
            this._super();
            this.initRuleListener();
        },
        
        initObservable: function () {
            this._super();
            this.ruleObject = {};
            this.observe('ruleObject value');
            
            return this;
        },
        
        initRuleListener: function () {
            var observer = new MutationObserver(function () {
                var rootNode = document.getElementById(this.index);
                if (rootNode !== null) {
                    this.rootNode = document.getElementById(this.index);
                    observer.disconnect();
                    var ruleObserver = new MutationObserver(this.updateRule.bind(this));
                    var ruleObserverConfig = {childList:true, subtree: true, attributes: true};
                    ruleObserver.observe(rootNode, ruleObserverConfig);
                    this.updateRule();
                }
            }.bind(this));
            var observerConfig = {childList: true, subtree: true};
            observer.observe(document, observerConfig)
        },
        
        updateRule: function () {
            var ruleObject = {};
            var hashValues = [];
            
            $(this.rootNode).find("[name*=" + this.index + "]").each(function () {
                hashValues.push(this.name + this.value.toString());
                var currentRuleObject = ruleObject;

                var path = this.name.match(/\[([^[\[\]]+)\]/g)
                               .map(function (pathItem) { return pathItem.substr(1, pathItem.length-2); });

                while (path.length > 1) {
                    var currentKey = path.shift();
                    
                    if (currentRuleObject[currentKey] === undefined) {
                        currentRuleObject[currentKey] = {};
                    }
                    
                    currentRuleObject = currentRuleObject[currentKey];
                }
                
                currentKey = path.shift();
                currentRuleObject[currentKey] = $(this).val();
            });
            
            var newHashValue = hashValues.sort().join('');
            
            if (newHashValue !== this.currentHashValue) {
                if (this.currentHashValue !== undefined) {
                    this.bubble('update', true);
                }
                this.currentHashValue = newHashValue;
                this.ruleObject(ruleObject);
                this.value(ruleObject);
            }
        }
    })
});
