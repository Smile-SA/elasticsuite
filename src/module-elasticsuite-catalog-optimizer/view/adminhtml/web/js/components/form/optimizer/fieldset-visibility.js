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