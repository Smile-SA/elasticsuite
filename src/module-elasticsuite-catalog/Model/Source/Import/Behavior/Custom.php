<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace  Smile\ElasticsuiteCatalog\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;

/**
 * Custom import behavior source model used for defining the behavior during the product attributes import.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class Custom extends AbstractBehavior
{
    /**
     * Get array of possible values.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Update'),
        ];
    }

    /**
     * Get current behaviour group code.
     *
     * @return string
     */
    public function getCode()
    {
        return 'elasticsuite_product_attribute_import_custom_behavior';
    }
}
