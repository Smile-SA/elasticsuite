/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

/**
 * Category chooser of the boundary categories for virtual rules setting, as in the rule conditions: the folder icon
 * opens a checkbox category tree, built from the ids currently in the input, which writes the ids of the checked
 * categories back into the input, and the apply icon closes it.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var input = document.getElementById(config.inputId),
            container = $('#' + config.containerId);

        // The tree writes the checked ids into the "updateElement" of this global form object.
        window[config.jsFormObject] = {updateElement: input};

        // "Use system value" disables the input and, when toggled, hides the icons of the row (Magento's
        // toggleValueElements()): hide them on load too, and close the tree, which must not be used to change it.
        if (input.disabled) {
            $(element).find('img').hide();
        }

        $('#' + config.inputId + '_inherit').on('change', function () {
            container.hide();
        });

        $(element).find('a.rule-param-apply').on('click', function (event) {
            event.preventDefault();
            container.hide();
        });

        $(element).find('a.rule-chooser-trigger').on('click', function (event) {
            var selected;

            event.preventDefault();

            if (input.disabled || container.is(':visible')) {
                container.hide();

                return;
            }

            selected = input.value.split(',').map(function (id) {
                return id.trim();
            }).filter(function (id) {
                return id !== '';
            });

            $.ajax({
                url: config.url,
                type: 'POST',
                data: {'form_key': window.FORM_KEY, selected: selected},
                showLoader: true
            }).done(function (html) {
                container.html(html).show().trigger('contentUpdated');
            });
        });
    };
});
