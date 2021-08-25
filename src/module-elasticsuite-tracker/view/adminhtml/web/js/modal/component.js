<!--
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
 -->
define([
    'underscore',
    'jquery',
    'Magento_AdminAnalytics/js/modal/component',
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
                 * Initialize modal's content components
                 */
                initializeContent: function () {
                    $.async({ component: this.name }, this.initModal);
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
                    this.openOtherPopup();
                    this.closeModal();
                },

                /**
                 * Allows admin usage popup to be shown first and then new release notification
                 */
                openOtherPopup: function () {
                    if (elasticPopupConfig.analyticsVisible) {
                        registry.get('admin_usage_notification.admin_usage_notification.notification_modal_1')
                            .initializeContentAfterElasticsuite();
                    } else if (elasticPopupConfig.releaseVisible) {
                        registry.get('release_notification.release_notification.notification_modal_1')
                            .initializeContentAfterElasticsuite();
                    }
                }
            }
        );
    }
);
