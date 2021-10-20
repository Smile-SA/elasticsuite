/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'MutationObserver'
], function (Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
            fallbackResetTpl: 'Smile_ElasticsuiteCatalogOptimizer/form/element/constant_score/slider',
            showFallbackReset: true,
            isDifferedFromDefault: true,
            sliderNotice: false,
            messages : {
                sliderNotice : $.mage.__('Using extreme boost values complicates achieving a balance between optimizers.'),
            },
            sliderConfig : {
                initialValue: 0,
                resetValue: 0,
                minValue: 0,
                maxValue: 100,
                step: 1,
            },
        },
        initialize: function () {
            this._super();
            this.sliderUid = this.uid + '_slider';

            this.observe([
                'sliderInitialValue',
                'sliderMinValue',
                'sliderMaxValue',
                'sliderStep',
                'sliderNotice',
            ]);
        },
        createSlider: function () {
            this.initSliderValue();
            this.slider.slider({
                value: this.sliderInitialValue(),
                min: this.sliderMinValue() ,
                max: this.sliderMaxValue(),
                step: this.sliderStep(),
                slide: $.proxy(function (event, ui) {
                    this.input.val(ui.value);
                    this.sliderNotice(false);
                }, this),
            });
        },
        initSliderValue: function () {
            this.sliderInitialValue(Number(this.sliderConfig.initialValue));
            this.sliderMinValue(Number(this.sliderConfig.minValue));
            this.sliderMaxValue(Number(this.sliderConfig.maxValue));
            this.sliderStep(Number(this.sliderConfig.step));

            this.input = $('#' + this.uid);
            this.slider = $('#' + this.sliderUid);

            if (this.input.val() === '' || this.input.val() === undefined) {
                this.input.val(this.sliderInitialValue());
            } else {
                if (this.input.val() >= this.sliderMinValue() && this.input.val() <= this.sliderMaxValue()) {
                    this.sliderInitialValue(Number(this.input.val()));
                } else if (this.input.val() < this.sliderMinValue()) {
                    this.sliderNotice(true);
                    this.sliderInitialValue(Number(this.sliderMinValue()));
                } else if (this.input.val() > this.sliderMaxValue()) {
                    this.sliderNotice(true);
                    this.sliderInitialValue(Number(this.sliderMaxValue()));
                }
            }
        },
        onUpdate: function (value) {
            this.sliderNotice(false);
            if (this.slider === undefined) {
                return this._super();
            }

            if (value === '' || value === undefined) {
                this.slider.slider('value', this.sliderConfig.initialValue);
                return this._super();
            }

            if (value >= this.sliderMinValue() && value <= this.sliderMaxValue()) {
                this.slider.slider('value', value);
            } else if (value < this.sliderMinValue()) {
                this.sliderNotice(true);
                this.slider.slider('value', Number(this.sliderMinValue()));
            } else if (value > this.sliderMaxValue()) {
                this.sliderNotice(true);
                this.slider.slider('value', Number(this.sliderMaxValue()));
            }

            return this._super();
        },
        setDifferedFromDefault: function () {
            // To keep the reset block always displayed.
            this.isDifferedFromDefault(true);
        }
    });
});
