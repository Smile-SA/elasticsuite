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

define('Smile_ElasticsuiteAnalytics/js/report/customer-company-selector', [
    'jquery',
    'mage/url'
], function($, urlBuilder) {
    'use strict';

    return function() {
        // On document ready, set the selected value in the company dropdown.
        $(document).ready(function() {
            var urlParams = new URLSearchParams(window.location.search);
            var selectedCompany = urlParams.get('company_id');

            if (selectedCompany) {
                $('#company_id').val(selectedCompany);
            }
        });

        // Handle the company dropdown value change.
        $('#company_id').on('change', function() {
            var selectedCompany = $(this).val();
            var newUrl = new URL(window.location.href);

            newUrl.searchParams.set('company_id', selectedCompany);

            // Redirect to the new URL with the company filter.
            window.location.href = newUrl.href;
        });
    };
});
