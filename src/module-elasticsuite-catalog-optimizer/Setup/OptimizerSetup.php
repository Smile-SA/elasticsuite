<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Generic Catalog Optimizer Setup
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class OptimizerSetup
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\DB\FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * Class Constructor
     *
     * @param \Magento\Framework\EntityManager\MetadataPool   $metadataPool              Metadata Pool.
     * @param \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory Field Data Converter Factory.
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory
    ) {
        $this->metadataPool              = $metadataPool;
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
    }

    /**
     * Upgrade legacy serialized data to JSON data.
     * Targets :
     *  - columns "config" and "rule_condition" of the smile_elasticsuite_optimizer table.
     *
     * @param \Magento\Setup\Module\DataSetup $setup Setup
     *
     * @return void
     */
    public function convertSerializedRulesToJson(\Magento\Setup\Module\DataSetup $setup)
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(
            \Magento\Framework\DB\DataConverter\SerializedToJson::class
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable(OptimizerInterface::TABLE_NAME),
            OptimizerInterface::OPTIMIZER_ID,
            OptimizerInterface::CONFIG
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable(OptimizerInterface::TABLE_NAME),
            OptimizerInterface::OPTIMIZER_ID,
            OptimizerInterface::RULE_CONDITION
        );
    }
}
