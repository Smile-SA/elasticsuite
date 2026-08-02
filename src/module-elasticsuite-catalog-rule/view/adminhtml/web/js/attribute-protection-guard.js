/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, registry, alert, $t) {
    'use strict';

    return function (config) {
        const attributeId = config.attributeId;
        const validationUrl = config.validationUrl;
        const modalTitle = $t('Attribute Protection System');

        if (!attributeId || attributeId === 0) {
            return;
        }

        /**
         * Query backend validation routing asynchronously with form_key validation.
         */
        function checkUsageLock(callback) {
            $.ajax({
                url: validationUrl,
                data: {
                    attribute_id: attributeId,
                    form_key: window.FORM_KEY
                },
                dataType: 'json',
                type: 'POST',
                showLoader: true
            }).done(function (response) {
                if (response && response.allowed !== undefined) {
                    callback(response.allowed, response.message);
                } else {
                    // Safe fallback if response format is unexpected.
                    callback(true, '');
                }
            }).fail(function () {
                // Fallback on request failure.
                callback(true, '');
            });
        }

        // Standard HTML Form Listener (Handles legacy htmlContent Tabs).
        $(document).on('change', 'select[name="is_used_for_promo_rules"], select#is_used_for_promo_rules', function () {
            const $select = $(this);

            if ($select.val() === '0') {
                checkUsageLock(function (isAllowed, message) {
                    if (!isAllowed) {
                        alert({
                            title: modalTitle,
                            content: message
                        });
                        // Snap raw HTML element selection back to "Yes".
                        $select.val('1');
                    }
                });
            }
        });

        // UI Component Fallback Wrapper (For true UI Form implementations).
        registry.async('index = is_used_for_promo_rules')(function (component) {
            let priorFlagState = component.value();

            component.value.subscribe(function (newValue) {
                if (priorFlagState == '1' && newValue == '0') {
                    checkUsageLock(function (isAllowed, message) {
                        if (!isAllowed) {
                            alert({
                                title: modalTitle,
                                content: message
                            });
                            // Revert UI element state back to "Yes".
                            component.value('1');
                        } else {
                            priorFlagState = newValue;
                        }
                    });
                } else {
                    priorFlagState = newValue;
                }
            });
        });

        // Intercept Delete Action Button Execution flow.
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('#delete');

            if (deleteBtn) {
                if (deleteBtn.getAttribute('data-verified') === 'true') {
                    return;
                }

                e.stopImmediatePropagation();
                e.preventDefault();

                checkUsageLock(function (isAllowed, message) {
                    if (!isAllowed) {
                        alert({
                            title: modalTitle,
                            content: message
                        });
                    } else {
                        deleteBtn.setAttribute('data-verified', 'true');
                        deleteBtn.click();

                        setTimeout(function () {
                            deleteBtn.removeAttribute('data-verified');
                        }, 500);
                    }
                });
            }
        }, true);
    };
});
