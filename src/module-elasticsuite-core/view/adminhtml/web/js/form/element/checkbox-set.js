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
    'Magento_Ui/js/form/element/checkbox-set'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Smile_ElasticsuiteCore/form/element/checkbox-set',
            optionsTooltips: {}
        },

        initialize: function ()
        {
            this._super();
        }
    });
});
