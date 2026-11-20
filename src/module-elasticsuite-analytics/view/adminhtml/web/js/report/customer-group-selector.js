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
    'jquery'
], function($) {
    'use strict';

    return function(config) {
        // Handle the customer group dropdown value change.
        $('#customer_group').on('change', function() {
            let selectedGroup = $(this).val();

            /*
             * Force-cast to a plain integer customer group ID ("all" is the one allowed exception),
             * so the value substituted into the URL can never be anything but a digit sequence.
             */
            if (selectedGroup !== 'all') {
                selectedGroup = parseInt(selectedGroup, 10);
                if (isNaN(selectedGroup)) {
                    return;
                }
            }

            window.location = config.baseUrl.replace('__customer_group__', selectedGroup);
        });
    };
});

