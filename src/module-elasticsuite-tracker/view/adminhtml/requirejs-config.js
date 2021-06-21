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
var config = {
    config: {
        mixins: {
            'Magento_ReleaseNotification/js/modal/component': {
                'Smile_ElasticsuiteTracker/js/release-notification/modal/component-mixin': true
            },
            'Magento_AdminAnalytics/js/modal/component': {
                'Smile_ElasticsuiteTracker/js/analytics-notification/modal/component-mixin': true
            }
        }
    }
};
