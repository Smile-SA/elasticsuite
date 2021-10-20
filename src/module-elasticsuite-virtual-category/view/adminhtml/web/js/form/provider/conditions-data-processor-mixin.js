/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'mage/utils/wrapper',
    'underscore',
    'mage/utils/objects',
    'Magento_Rule/conditions-data-normalizer'
], function (wrapper, _, objectUtils, ConditionsDataNormalizer) {
    'use strict';

    var serializer = new ConditionsDataNormalizer();

    // Rewrite magento condition provider in order to change used condition classes.
    // @see https://github.com/magento/magento2/issues/34333

    return function (conditionsDataProcessorFunction) {
        return wrapper.wrap(conditionsDataProcessorFunction, function (conditionsDataProcessor, data, attribute) {
            // add extended functionality here
            var pairs = {},
                conditions = '';

            /*
             * The Condition Rule Tree is not a UI component and doesn't provide good data.
             * The best solution is to implement the tree as a UI component that can provide good data but
             * that is outside of the scope of the feature for now.
             */
            _.each(data, function (element, key) {
                // parameters is hardcoded in the Magento\Rule model that creates the HTML forms.
                if (key.indexOf('parameters[' + attribute + ']') === 0) {
                    // Remove the bad, un-normalized data.
                    delete data[key];
                    pairs[key] = element;
                }
            });

            /*
             * The Combine Condition rule needs to have children,
             * if does not have, we cannot parse the rule in the backend.
             */
            _.each(pairs, function (element, key) {
                var keyIds = key.match(/[\d?-]+/g),
                    combineElement = 'Smile\\ElasticsuiteVirtualCategory\\Model\\Rule\\WidgetCondition\\Combine',
                    nextPairsFirstKey = 'parameters[condition_source][NEXT_ITEM--1][type]',
                    nextPairsSecondKey = 'parameters[condition_source][NEXT_ITEM--2][type]';

                if (keyIds !== null && element === combineElement) {
                    if (pairs[nextPairsFirstKey.replace('NEXT_ITEM', keyIds[0])] === undefined ||
                        pairs[nextPairsFirstKey.replace('NEXT_ITEM', keyIds[0])] === combineElement &&
                        pairs[nextPairsSecondKey.replace('NEXT_ITEM', keyIds[0])] === undefined) {
                        pairs[key] = '';
                    }
                }
            });

            /*
             * Add pairs in case conditions source is not rules configurator
             */
            if (data['condition_option'] !== 'condition') {
                pairs['parameters[' + attribute + '][1--1][operator]'] =
                    data[data['condition_option'] + '-condition_operator'] ?
                        data[data['condition_option'] + '-condition_operator'] :
                        '==';
                pairs['parameters[' + attribute + '][1--1][type]'] =
                    'Smile\\ElasticsuiteVirtualCategory\\Model\\Rule\\WidgetCondition\\Product';
                pairs['parameters[' + attribute + '][1][aggregator]'] = 'all';
                pairs['parameters[' + attribute + '][1][new_child]'] = '';
                pairs['parameters[' + attribute + '][1][type]'] = 'Smile\\ElasticsuiteVirtualCategory\\Model\\Rule\\WidgetCondition\\Combine';
                pairs['parameters[' + attribute + '][1][value]'] = '1';
                pairs['parameters[' + attribute + '][1--1][attribute]'] = data['condition_option'];
                pairs['parameters[' + attribute + '][1--1][value]'] = _.isString(data[data['condition_option']]) ?
                    data[data['condition_option']].trim() :
                    '';
            }

            if (!_.isEmpty(pairs)) {
                conditions = JSON.stringify(serializer.normalize(pairs).parameters[attribute]);
                data['conditions_encoded'] = conditions;
                objectUtils.nested(data, attribute, conditions);
            }
        });
    };
});
