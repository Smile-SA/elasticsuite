/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['Magento_Ui/js/form/components/fieldset'], function(Fieldset) {
    return Fieldset.extend({
        defaults: {
            imports: {
                "initValue": "${ $.provider }:data.model"
            },
            listens: {
                "${ $.provider }:data.model" : "onModelChange"
            }
        },
        initialize: function() {
            this._super();
            this.observe(['disableChildren']);
            this.onModelChange(this.initValue);
        },
        onModelChange: function(value)  {
            var isVisible = this.index == value;
            this.visible(isVisible);
            this.disableChildren(!isVisible);
        }
    });
});
