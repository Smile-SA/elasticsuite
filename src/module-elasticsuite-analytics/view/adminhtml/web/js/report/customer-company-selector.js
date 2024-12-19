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
    'jquery'
], function($) {
    'use strict';

    return function(config) {
        // Handle the company dropdown value change.
        $('#company_id').on('change', function() {
            let selectedCompany = $(this).val();

            window.location = config.baseUrl.replace('__company_id__', selectedCompany);
        });
    };
});
