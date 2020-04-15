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
use Smile\ElasticsuiteIndices\Model\IndexMappingProvider;

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
     * @var IndexMappingProvider
     */
    protected $indexMappingProvider;

    /**
     * @var string
     */
    private $name;

    /**
     * @param EntityFactoryInterface $entityFactory        Entity factory.
     * @param IndexMappingProvider   $indexMappingProvider Index mapping provider.
     * @param string                 $name                 Index name.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        IndexMappingProvider $indexMappingProvider,
        string $name
    ) {
        parent::__construct($entityFactory);

        $this->indexMappingProvider = $indexMappingProvider;
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
        $mapping = $this->indexMappingProvider->getMapping($this->name);
        $this->_items = array_shift($mapping[$this->name]['mappings']);

        return $this;
    }
}
