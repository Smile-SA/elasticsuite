define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal'
], function (_, uiRegistry, Select, modal) {
    'use strict';

    return Select.extend({

        /**
         * Component initializing
         *
         * @returns {exports}
         */
        initialize: function () {
            this._super();

            this.onUpdate(this.value());
            return this;
        },

        /**
         * Hide/Show fields depending of the type of container selected for previewing.
         *
         * @param value Current Value
         * @returns {*}
         */
        onUpdate: function (value) {
            var isFulltext = this.isFulltextContainer(value);

            var depends = uiRegistry.filter('depends = ' + this.index);
            depends.forEach(function (element) {
                if (element.fulltext !== undefined) {
                    var visible = element.fulltext === isFulltext;
                    element.setVisible(visible);
                    if (!visible) {
                        element.value(null);
                    }
                }
            }, this);

            return this._super();
        },

        /**
         * Check if currently selected container is a fulltext one or not.
         *
         * @param currentContainer A search request container
         * @returns {boolean}
         */
        isFulltextContainer: function(currentContainer) {

            var result = false;

            this.options().forEach(function(container) {
                if (currentContainer === container.value) {
                    result = (container.fulltext !== undefined) && (container.fulltext === true);
                }
            }, this);

            return result;
        }
    });
});
