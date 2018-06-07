/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['Magento_Ui/js/dynamic-rows/record'], function (Component) {
    //'use strict';

    return Component.extend({
        defaults: {
            enableDnd : false,
            listens : {
                'data.is_pinned'          : 'onPinChange',
                'data.facet_display_mode' : 'toggleMinCoverageRate'
            }
        },

        /**
         * Init bindings.
         *
         * @returns {exports}
         */
        initObservable: function () {
            this._super().observe(['isPinned']);
            return this;
        },

        /**
         * Called when 'is_pinned' property is modified. When pinning/unpinning an item.
         */
        onPinChange : function() {
            if (this.data() && this.data().is_pinned !== undefined) {
                this.isPinned(this.data().is_pinned);
                if (this.isPinned()) {
                    this.position = this.parentComponent().getPinnedRecords().length + 1;
                }
                this.parentComponent().sort(this.position, this);
            }
        },

        /**
         * Toggle "Min Coverage Rate" children.
         * Disable it when record is set to other value than "Auto" for Display Mode.
         */
        toggleMinCoverageRate: function() {
            if (this.data() && this.data().facet_display_mode) {
                if (this.elems().length > 0) {
                    var facetCoverageIndex = this.getChildrenIndex('facet_min_coverage_rate');
                    if (facetCoverageIndex !== -1) {
                        var isAuto = (parseInt(this.data().facet_display_mode, 10) === 0);
                        this.elems()[facetCoverageIndex].canEdit(isAuto);
                        if (!isAuto) {
                            this.elems()[facetCoverageIndex].isUseDefault(true);
                        }
                    }
                }
            }
        },

        /**
         * Overridden method to use it on children initialization.
         */
        setColumnVisibileListener: function () {
            this._super();
            this.toggleMinCoverageRate();
        },

        /**
         * Find index og a given children into record children.
         *
         * @param name
         * @returns {Integer}
         */
        getChildrenIndex: function (name) {
            return this.elems().findIndex(function (elem) {
                return elem.index === name
            });
        }
    });
});
