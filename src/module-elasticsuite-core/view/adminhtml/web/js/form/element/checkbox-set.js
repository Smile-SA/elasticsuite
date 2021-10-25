/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Ui/js/form/element/checkbox-set',
    'jquery',
    'mage/template',
    'text!Smile_ElasticsuiteCore/template/form/element/helper/tooltip.html',
    'domReady'
], function (Component, $, mageTemplate, tooltipTmpl, domReady) {
    'use strict';

    return Component.extend({
        defaults: {
            tooltips: {},
        },

        initialize: function () {
            this._super();

            /**
             * baseTmpl: Used to display the native magento template.
             * tooltipsTmpl: Contains a div to have the possibility to call initToolTips function after render.
             * template: Contains render HTML tags to display baseTmpl and tooltipsTmpl.
             */
            this.baseTmpl = this.template;
            this.tooltipsTmpl = 'Smile_ElasticsuiteCore/form/element/helper/tooltips';
            this.template = 'Smile_ElasticsuiteCore/form/element/checkbox-set';
        },

        initToolTips: function () {
            Object.entries(this.tooltips).forEach(function (tooltip) {
                var tooltipContent = mageTemplate(
                    tooltipTmpl,
                    { description : tooltip[1]}
                );
                let radioInput = $('legend[for='+ this.uid+']').parent().find('input[value=' + tooltip[0] + ']');
                radioInput.parent().append(tooltipContent);
            }, this);
        },
    });
});
