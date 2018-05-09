/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.s
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['jquery', 'mage/cookies'], function ($) {
    return function(config) {
        return config.cookieRestrictionEnabled == false || $.mage.cookies.get(config.cookieRestrictionName) !== null;
    };
})
