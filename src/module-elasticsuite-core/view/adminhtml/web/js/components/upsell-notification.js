/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
define([
        'underscore',
        'jquery',
        'Magento_Ui/js/form/components/html',
        'uiRegistry',
    ],
    function (_, $, Component, registry) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    imports: {
                        validateNotificationAction: '${ $.provider }:data.validateNotificationAction',
                        visible: true,
                    },
                    options: {},
                },

                /**
                 * Dismiss the Upsell notification
                 */
                validateNotification: function () {
                    var data = {
                        'form_key': window.FORM_KEY
                    };

                    $.ajax(
                        {
                            type: 'POST',
                            url: this.validateNotificationAction,
                            data: data,
                            showLoader: false
                        }
                    ).done(
                        function (xhr) {
                            if (xhr.error) {
                                self.onError(xhr);
                            }
                        }
                    ).fail(this.onError);
                    this.visible(false);
                }
            }
        );
    }
);
