/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['Magento_Ui/js/form/components/fieldset'], function(Fieldset) {
    return Fieldset.extend({
        defaults: {
            imports: {
                "initValue": "${ $.provider }:data.model"
            },
            listens: {
                "${ $.provider }:data.model" : "onChange"
            }
        },
        initialize: function() {
            this._super();
            this.observe(['disableChildren']);
            this.onChange(this.initValue);
        },
        onChange: function(value)  {
            var isVisible = this.index == value;
            if (Array.isArray(value)) {
                isVisible = (value.indexOf(this.index) !== -1);
            }
            this.visible(isVisible);
            this.disableChildren(!isVisible);
        }
    });
});
