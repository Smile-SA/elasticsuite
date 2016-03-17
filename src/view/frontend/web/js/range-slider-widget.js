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
/*global alert*/

define(["jquery", 'Magento_Catalog/js/price-utils', 'mage/template', "jquery/ui"], function ($, priceUtil, mageTemplate) {
    
    "use strict";
    
    $.widget('smileEs.rangeSlider', {
        
        options: {
            fromLabel   : '[data-role=from-label]',
            toLabel     : '[data-role=to-label]',
            sliderBar   : '[data-role=slider-bar]',
            message     : '[data-role=message-box]',
            applyButton : '[data-role=apply-range]',
            messageTemplates : {
                "displayCount": '<span class="msg"><%- count %> items</span>',
                "displayEmpty": '<span class="msg-error">No items in the current range.</span>'
            }
        },
        
        _create: function () {
            this.from = this.options.currentValue.from;
            this.to   = this.options.currentValue.to;
            this._createSlider();
            this._refreshDisplay();
            this.element.find(this.options.applyButton).bind('click', this._applyRange.bind(this));
        },
        
        _createSlider: function() {
            this.element.find(this.options.sliderBar).slider({
                range: true,
                min: this.options.minValue,
                max: this.options.maxValue,
                values: [ this.from, this.to ],
                slide: this._onSliderChange.bind(this)
            });
        },
        
        _onSliderChange : function (ev, ui) {
            this.from = ui.values[0];
            this.to   = ui.values[1];
            this._refreshDisplay();
        },
        
        _refreshDisplay: function() {
            this.count = this._getItemCount();
            
            if (this.element.find('[data-role=from-label]')) {
                this.element.find('[data-role=from-label]').html(this._formatLabel(this.from));
            }
            
            if (this.element.find('[data-role=to-label]')) {
                this.element.find('[data-role=to-label]').html(this._formatLabel(this.to));
            }
            
            if (this.element.find('[data-role=message-box]')) {
                var messageTemplate = this.options.messageTemplates[this.count > 0 ? 'displayCount' : 'displayEmpty'];
                var message = mageTemplate(messageTemplate)(this);
                this.element.find('[data-role=message-box]').html(message);
                
                if (this.count > 0) {
                    this.element.find('[data-role=message-box]').removeClass('empty');
                } else {
                    this.element.find('[data-role=message-box]').addClass('empty');
                }
                
            }
        },
        
        _applyRange : function () {
            var url = mageTemplate(this.options.urlTemplate)(this);
            this.element.find(this.options.applyButton).attr('href', url);
        },
        
        
        _getItemCount : function() {
            var from = this.from, to = this.to, intervals = this.options.intervals;
            var count = intervals.map(function(item) {return item.value >= from && item.value < to ? item.count : 0;})
                                 .reduce(function(a,b) {return a + b;});
            return count;
        },
        
        _formatLabel : function(value) {
            var formattedValue = value;
            
            if (this.options.priceFormat) {
                formattedValue = priceUtil.formatPrice(value, this.options.priceFormat);
            }
            
            return formattedValue;
        }
        
    });
    
    return $.smileEs.rangeSlider;
});