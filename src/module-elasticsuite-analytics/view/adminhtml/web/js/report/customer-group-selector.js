/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define('Smile_ElasticsuiteAnalytics/js/report/customer-group-selector', [
    'jquery',
    'mage/url'
], function($, urlBuilder) {
    'use strict';

    return function() {
        // !On document ready, set the selected value in the customer group dropdown.
        $(document).ready(function() {
            var urlParams = new URLSearchParams(window.location.search);
            var selectedGroup = urlParams.get('customer_group');

            if (selectedGroup) {
                $('#customer_group').val(selectedGroup);
            }
        });

        // Handle the customer group dropdown value change.
        $('#customer_group').on('change', function() {
            var selectedGroup = $(this).val();
            var newUrl = new URL(window.location.href);

            newUrl.searchParams.set('customer_group', selectedGroup);

            // Redirect to the new URL with the customer group filter.
            window.location.href = newUrl.href;
        });
    };
});

