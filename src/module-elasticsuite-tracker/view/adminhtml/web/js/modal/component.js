/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
define([
    'underscore',
    'jquery',
    'Magento_Ui/js/modal/modal-component',
    'uiRegistry',
    'elasticPopupConfig'
],
    function (_, $, Modal, registry, elasticPopupConfig) {
        'use strict';

        return Modal.extend(
            {
                defaults: {
                    imports: {
                        validateNotificationAction: '${ $.provider }:data.validateNotificationAction'
                    },
                    options: {},
                    notificationWindow: null
                },

                /**
                 * Validate elasticsuite global tracking usage setting
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
                            showLoader: true
                        }
                    ).done(
                        function (xhr) {
                            if (xhr.error) {
                                self.onError(xhr);
                            }
                        }
                    ).fail(this.onError);
                    this.closeModal();
                }
            }
        );
    }
);
