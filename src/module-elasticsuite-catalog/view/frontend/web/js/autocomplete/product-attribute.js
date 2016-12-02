/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

/*jshint browser:true jquery:true*/
/*global alert*/

define(['underscore'], function(_) {
    var Renderer = {
        render : function (data) {
            data = data.filter(function(item) {
                return item.type === "product_attribute";
            }).map(function(item) {
                return item['attribute_label']
            }).reduce(function(prev, item) {
                if (item in prev) {
                    prev[item]++;
                } else {
                    prev[item] = 1;
                }
                return prev;
            }, {});

            data = _.pairs(data).sort(function(item1, item2) {
                return item2[0] - item1[0]
            }).map(function(item) {return item[0]});

            if (data.length > 2) {
                data = data.slice(0, 2);
                data.push('...');
            }

            return data.join(', ');
        }
    }

    return Renderer;
});
