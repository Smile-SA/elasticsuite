<?php

namespace Smile\ElasticSuiteCore\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;

class Test extends Template
{
    /**
     * @var IndexOperationInterface
     */
    private $operation;

    /**
     *
     * @param Context $context
     * @param ClientConfigurationInterface $clientConfiguration
     *
     * @param array $data
     */
    public function __construct(Context $context, IndexOperationInterface $indexOperation, array $data = [])
    {
        parent::__construct($context, $data);
        $this->operation = $indexOperation;
    }

    /**
     * @return string
     */
    public function getTest()
    {
        $data = "";
        if ($this->operation->isAvailable()) {
            $store = $this->_storeManager->getStore();
            try {
                $index = $this->operation->createIndex('catalog_product', $store);
                $this->operation->installIndex($index, $store);
                $data = $index->getName();
                $index = $this->operation->getIndexByName('catalog_product', $store);
            } catch (\Exception $exception) {
                $data = $exception->getMessage();
            }
        }

        return print_r($data, true);
    }
}