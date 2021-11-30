/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['jquery'], function ($) {
    'use strict';

    return function (validator) {
        validator.addRule(
            'not-zero',
            function (value, element) {
                return parseInt(value) !== 0;
            },
            $.mage.__('The value should be different to zero.')
        );

        return validator;
    }
});

