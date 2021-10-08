/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategories
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

var config = {
    map: {
        '*': {
            'Magento_Catalog/catalog/category/assign-products': 'Smile_ElasticsuiteVirtualCategory/js/component/catalog/category/form/assign-products'
        }
    },
    config: {
        mixins: {
            'Magento_PageBuilder/js/form/provider/conditions-data-processor': {
                'Smile_ElasticsuiteVirtualCategory/js/form/provider/conditions-data-processor-mixin': true
            }
        }
    }
};
