<?php
/**
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vladimir Bratukhin <insyon@gmail.com>
 * @copyright 2017 Smile
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterface;
use Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterfaceFactory;

/**
 * Class Extensible
 *
 * @package Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource
 *
 * @author Vladimir Bratukhin <insyon@gmail.com>
 */
abstract class AbstractExtensible
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterfaceFactory
     */
    protected $dataExtensionInterfaceFactory;

    /**
     * Constructor.
     *
     * @param ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory DataExtension factory
     */
    public function __construct(
        ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory
    ) {
        $this->dataExtensionInterfaceFactory = $dataExtensionInterfaceFactory;
    }

    /**
     * Returns DataExtension object for product data array
     *
     * @param array $product Product data array
     *
     * @return ProductDataExtensionInterface
     */
    protected function getDataExtension(&$product)
    {
        return $product[ProductDataExtensionInterface::KEY] ?? (
            $product[ProductDataExtensionInterface::KEY]
                = $this->dataExtensionInterfaceFactory->create()
            );
    }
}
