<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Category\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData;
use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

/**
 * Datasource used to index product attributes.
 * This class is also used to generate attribute mapping since it implements DynamicFieldProviderInterface.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeData extends AbstractAttributeData implements DatasourceInterface, DynamicFieldProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $categoryIds   = array_keys($indexData);

        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $categoryIds, $backendTable, $attributeIds);
            foreach ($attributesData as $row) {
                $productId = (int) $row['entity_id'];
                $attribute = $this->attributesById[$row['attribute_id']];

                $indexValues = $this->attributeHelper->prepareIndexValue(
                    $attribute,
                    $storeId,
                    $row['value']
                );

                $indexData[$productId] += $indexValues;
            }
        }

        return $indexData;
    }
}
