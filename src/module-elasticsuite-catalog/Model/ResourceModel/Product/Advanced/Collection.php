<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Advanced;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as FulltextCollection;

/**
 * Advanced Search Product Collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends FulltextCollection
{
    /**
     * Add multiple fields to filter
     *
     * @param array $fields The fields to filter
     *
     * @return $this
     */
    public function addFieldsToFilter($fields)
    {
        if ($fields) {
            $callback = function ($element) {
                return is_array($element) ? true : strlen($element);
            };

            foreach ($fields as $fieldByType) {
                foreach ($fieldByType as $attributeId => $condition) {
                    $attributeCode = $this->getEntity()->getAttribute($attributeId)->getAttributeCode();
                    $condition     = array_filter($condition, $callback);
                    $this->addFieldToFilter($attributeCode, $condition);
                }
            }
        }

        return $this;
    }
}
