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
define(['jquery', 'elasticPopupConfig'], function ($, elasticPopupConfig) {
    'use strict';

    let deferred = $.Deferred(),
        mixin = {
            /**
             * Initializes content only if its visible
             */
            initializeContent: function () {
                let initializeContent = this._super.bind(this);

                if (!elasticPopupConfig.elasticsuiteVisible) {
                    initializeContent();
                } else {
                    deferred.then(function () {
                        initializeContent();
                    });
                }
            },

            /**
             * Initializes release notification content after admin analytics
             */
            initializeContentAfterElasticsuite: function () {
                deferred.resolve();
            }
        };

    return function (target) {
        return target.extend(mixin);
    };
});

