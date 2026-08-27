/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
define([
    'Magento_Ui/js/form/element/ui-select'
], function (UiSelect) {
    'use strict';

    return UiSelect.extend({
        /**
         * Reloads the page with the newly selected company_id (or without it, for "All Companies"),
         * preserving every other existing path-segment param.
         *
         * @param {Object} data - selected option data.
         * @returns {Object} Chainable.
         */
        toggleOptionSelected: function (data) {
            this._super();
            this.reloadWithCompanyId(data.value);

            return this;
        },

        /**
         * Preloads the first page (alphabetical) the first time the field is opened, so the list isn't
         * empty until the admin types something. Gated to once: loadOptions() advances its own paging
         * cursor when called again with the same search key, so re-opening would otherwise fetch page 2.
         *
         * @returns {Object} Chainable.
         */
        toggleListVisible: function () {
            this._super();

            if (this.listVisible() && !this.optionsPreloaded) {
                this.optionsPreloaded = true;
                // lastSearchKey defaults to '', same as this preload's search key, which would make
                // loadOptions() think this is page 2 of an already-completed empty search.
                this.lastSearchKey = null;
                this.loadOptions('');
            }

            return this;
        },

        /**
         * Handles the "remove selected" (x) icon: abstract.js's clear() bypasses toggleOptionSelected.
         *
         * @returns {Object} Chainable.
         */
        clear: function () {
            this._super();
            this.reloadWithCompanyId('');

            return this;
        },

        /**
         * Rebuilds company_id as a path segment (Magento admin's own URL convention) rather than a
         * query string, so it doesn't collide with other filter widgets rebuilding the same URL.
         *
         * @param {String} companyId
         */
        reloadWithCompanyId: function (companyId) {
            var segments = window.location.pathname.split('/').filter(function (segment) {
                    return segment !== '';
                }),
                paramIndex = segments.indexOf('company_id');

            if (paramIndex !== -1) {
                segments.splice(paramIndex, 2);
            }

            if (companyId) {
                segments.push('company_id', companyId);
            }

            window.location.assign(window.location.origin + '/' + segments.join('/') + '/');
        }
    });
});
