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
            foreach ($fields as $fieldByType) {
                foreach ($fieldByType as $attributeId => $condition) {
                    $attributeCode = $this->getEntity()->getAttribute($attributeId)->getAttributeCode();
                    $condition     = $this->cleanCondition($condition);

                    if (null !== $condition) {
                        $this->addFieldToFilter($attributeCode, $condition);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Ensure proper building of condition
     *
     * @param array|string $condition The condition to apply
     *
     * @return array|string|null
     */
    private function cleanCondition($condition)
    {
        if (is_array($condition)) {
            $condition = array_filter($condition);
            if (empty($condition)) {
                $condition = null;
            }
        }

        return $condition;
    }
}
