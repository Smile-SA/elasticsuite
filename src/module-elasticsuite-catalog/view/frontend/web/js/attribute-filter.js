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

define([
    'jquery',
    'uiComponent',
], function ($, L) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Smile_ElasticsuiteCatalog/attribute-filter"
        }
    });
});