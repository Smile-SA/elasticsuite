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


/*jshint browser:true jquery:true*/
/*global alert*/

define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/template',
    'Smile_ElasticsuiteCatalog/js/slider',
    'Magento_Ui/js/modal/modal',
    'Smile_ElasticsuiteCatalog/js/jquery.ui.touch-punch.min'
], function ($, priceUtil, mageTemplate) {

    "use strict";

    $.widget('smileEs.rangeSlider', {

        options: {
            fromLabel      : '[data-role=from-label]',
            toLabel        : '[data-role=to-label]',
            sliderBar      : '[data-role=slider-bar]',
            message        : '[data-role=message-box]',
            applyButton    : '[data-role=apply-range]',
            rate           : 1.0000,
            maxLabelOffset : 0.01,
            messageTemplates : {
                "displayOne": '<span class="msg">1 item</span>',
                "displayCount": '<span class="msg"><%- count %> items</span>',
                "displayEmpty": '<span class="msg-error">No items in the current range.</span>'
            },
        },

        _create: function () {
            this.showAdaptiveSlider = this.options.showAdaptiveSlider;
            if (this.showAdaptiveSlider) {
                this._initAdaptiveSliderValues();
            } else {
                this._initSliderValues();
            }

            this._createSlider();
            this._refreshDisplay();
            this.element.find(this.options.applyButton).bind('click', this._applyRange.bind(this));
        },

        _initSliderValues: function () {
            this.rate         = parseFloat(this.options.rate);
            this.from         = Math.floor(this.options.currentValue.from * this.rate);
            this.to           = Math.round(this.options.currentValue.to * this.rate);
            this.intervals    = this.options.intervals.map(
                function(item) { item.value = Math.round(item.value * this.rate); return item}.bind(this)
            );
            this.minValue = Math.floor(this.options.minValue * this.rate);
            this.maxValue = Math.round(this.options.maxValue * this.rate);
        },

        _initAdaptiveSliderValues: function () {
            this.intervals = this.options.adaptiveIntervals;
            this.from      = this._getAdaptiveValue(Number(this.options.currentValue.from));
            this.to        = this._getAdaptiveValue(Number(this.options.currentValue.to));
            this.rate      = parseFloat(this.options.rate);
            this.intervals = this.intervals.map(
                function(item) { item.originalValue = Math.ceil(item.originalValue * this.rate); return item}.bind(this)
            );
            this.minValue  = this.intervals[0].value;
            this.maxValue  = this.intervals[this.intervals.length - 1].value;
        },

        _createSlider: function () {
            this.element.find(this.options.sliderBar).slider({
                range: true,
                min: this.minValue,
                max: this.maxValue,
                values: [ this.from, this.to ],
                slide: this._onSliderChange.bind(this),
                step: this.options.step
            });
        },

        _onSliderChange : function (ev, ui) {
            this.from = this._getClosestAdaptiveValue(ui.values[0]);
            this.to   = this._getClosestAdaptiveValue(ui.values[1]);
            this._refreshDisplay();
        },

        _refreshDisplay: function () {
            this.count = this._getItemCount();

            if (this.element.find('[data-role=from-label]')) {
                this.element.find('[data-role=from-label]').html(this._formatLabel(this._getOriginalValue(this.from)));
            }

            if (this.element.find('[data-role=to-label]')) {
                var to = this._getOriginalValue(this.to) - this.options.maxLabelOffset;
                if (this.showAdaptiveSlider && to < this._getOriginalValue(this.minValue)) {
                    to = this._getOriginalValue(this.to);
                }
                this.element.find('[data-role=to-label]').html(this._formatLabel(to));
            }

            if (this.element.find('[data-role=message-box]')) {
                var messageTemplate = this.options.messageTemplates[this.count > 0 ? (this.count > 1 ? 'displayCount' : 'displayOne' ) : 'displayEmpty'];
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
            // Do not submit "rate applied" values. Revert the rate on submitted values.
            var range = {
                from : this._getOriginalValue(this.from) * (1 / this.rate),
                to   : this._getOriginalValue(this.to) * (1 / this.rate)
            };

            var url = mageTemplate(this.options.urlTemplate)(range);
            this.element.find(this.options.applyButton).attr('href', url);
        },

        _getAdaptiveValue : function (value) {
            if (!this.showAdaptiveSlider) {
                return value;
            }

            var adaptiveValue = this.intervals[0].value;
            var found = false;
            this.intervals.forEach(function (item) {
                if (found === false && item.originalValue === value) {
                    adaptiveValue = item.value;
                    found = true;
                }

                if (found === false && item.originalValue < value) {
                    adaptiveValue = item.value;
                }
            });

            return adaptiveValue;
        },

        _getClosestAdaptiveValue : function (value) {
            if (!this.showAdaptiveSlider) {
                return value;
            }

            var closestValue = this.intervals[0].value;
            var found = false;
            this.intervals.forEach(function (item) {
                if (item.value === value) {
                    closestValue = value;
                    found = true;
                }

                if (found === false && item.value < value) {
                    closestValue = item.value;
                }
            });

            return closestValue;
        },

        _getOriginalValue : function (value) {
            if (!this.showAdaptiveSlider) {
                return value;
            }

            var originalValue = null;
            this.intervals.forEach(function (item) {
                if (item.value === value) {
                    originalValue = item.originalValue;
                }
            });

            return originalValue;
        },

        _getItemCount : function () {
            var from = this.from, to = this.to, intervals = this.intervals;

            var count = intervals.map(function (item) {
                return (item.value >= from && (item.value < to || ((from === to) && item.value <= to))) ? item.count : 0;
            })
            .reduce(function (a,b) {
                return a + b;
            });
            return count;
        },

        _formatLabel : function (value) {
            var formattedValue = value;

            if (this.options.fieldFormat) {
                formattedValue = priceUtil.formatPrice(value, this.options.fieldFormat);
            }

            return formattedValue;
        }
    });

    return $.smileEs.rangeSlider;
});
