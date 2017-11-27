<?php

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
abstract class Extensible
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterfaceFactory
     */
    protected $dataExtensionInterfaceFactory;

    /**
     * Constructor.
     *
     * @param ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory
     */
    public function __construct(
        ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory
    ) {
        $this->dataExtensionInterfaceFactory = $dataExtensionInterfaceFactory;
    }

    /**
     * Returns DataExtension object for productData array
     *
     * @param array $product
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
