/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
define([
        'underscore',
        'jquery',
        'Magento_Ui/js/form/provider'
    ],
    function (_, $, Provider) {
        'use strict';

        return Provider.extend(
            {
                defaults: {
                    imports: {
                        validateNotificationAction: '${ $.provider }:data.validateNotificationAction',
                        visible: true,
                    },
                    options: {},
                },

                /**
                 * Saves currently available data.
                 * Overriden to do nothing. The upsell block is not a "real" form that submits data.
                 *
                 * @param {Object} [options] - Addtitional request options.
                 *
                 * @returns {Provider} Chainable.
                 */
                save: function (options) {
                    return this;
                },
            }
        );
    }
);
