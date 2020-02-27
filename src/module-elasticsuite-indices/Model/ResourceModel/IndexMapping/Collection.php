<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Model\ResourceModel\IndexMapping;

use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Smile\ElasticsuiteCore\Client\Client;

/**
 * Class Resource Model: Index Mapping Collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Collection extends DataCollection
{
    /**
     * @var Client
     */
    private $esClient;

    /**
     * @var string
     */
    private $name;

    /**
     * @param EntityFactoryInterface $entityFactory Entity factory.
     * @param Client                 $esClient      ElasticSearch client.
     * @param string                 $name          Index name.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Client $esClient,
        string $name
    ) {
        parent::__construct($entityFactory);

        $this->esClient = $esClient;
        $this->name = $name;

        $this->setItemObjectClass(DataObject::class);
    }

    /**
     * @param bool $printQuery Is print query.
     * @param bool $logQuery   Is log query.
     * @return Collection
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false): Collection
    {
        $data = [];
        $mapping = $this->esClient->getMapping($this->name);

        $mappingArray = array_shift($mapping[$this->name]['mappings']);

        if (!empty($mappingArray['properties'])) {
            foreach ($mappingArray['properties'] as $name => $item) {
                $data[] = $this->prepareItem($name, $item);
            }
        }
        $this->_items = $data;

        return $this;
    }

    /**
     * Prepare a index mapping item.
     *
     * @param string $propertyName Index property name.
     * @param array  $propertyItem Index property item.
     * @return DataObject
     */
    protected function prepareItem($propertyName, $propertyItem): DataObject
    {
        $dataItem = new DataObject();
        $dataItem->setData('name', $propertyName);
        if (!empty($propertyItem['type'])) {
            $dataItem->setData('type', $propertyItem['type']);
        }
        $data = [];
        if (!empty($propertyItem['properties'])) {
            foreach ($propertyItem['properties'] as $name => $item) {
                $data[] = $this->prepareItem($name, $item);
            }
            $dataItem->setData('properties', $data);
        }

        return $dataItem;
    }
}
