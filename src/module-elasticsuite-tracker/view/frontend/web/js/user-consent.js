/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['jquery', 'mage/cookies'], function ($) {
    'use strict';

    var hasUserConsent = function(configuration) {
        // If cookie restriction mode is not enabled, consider the user consent as accepted.
        var result = !isCookieRestrictionEnabled(configuration);

        // Otherwise, return true only if the user has accepted cookies.
        if (!result && configuration.hasOwnProperty('cookieRestrictionName')) {
            result = ($.mage.cookies.get(configuration.cookieRestrictionName) !== null);
        }

        return result;
    };

    var isCookieRestrictionEnabled = function(configuration) {
        return configuration.hasOwnProperty('cookieRestrictionEnabled')
            && configuration.cookieRestrictionEnabled === true;
    };

    return {

        /**
         * @param {Object} config
         * @constructor
         */
        'Smile_ElasticsuiteTracker/js/user-consent': function (config) {
            this.getUserConsent = function() {
                return hasUserConsent(config);
            }
        }
    };
});
