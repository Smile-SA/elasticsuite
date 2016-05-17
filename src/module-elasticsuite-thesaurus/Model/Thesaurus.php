<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteThesaurus\Model;

use Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface;
use Smile\ElasticSuiteThesaurus\Model\Indexer\Thesaurus as ThesaurusIndexer;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Thesaurus Model
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Thesaurus extends \Magento\Framework\Model\AbstractModel implements ThesaurusInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    // @codingStandardsIgnoreStart Property is inherited
    protected $_eventPrefix = 'smile_elasticsuite_thesaurus';
    // @codingStandardsIgnoreEnd

    /**
     * Parameter name in event
     * In observer method you can use $observer->getEvent()->getThesaurus() in this case
     *
     * @var string
     */
    // @codingStandardsIgnoreStart Property is inherited
    protected $_eventObject = 'thesaurus';
    // @codingStandardsIgnoreEnd

    /**
     * @var array The store ids of this thesaurus
     */
    private $storeIds = [];

    /**
     * @var array The term data of this thesaurus
     */
    private $termsData;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * PHP constructor
     *
     * @param \Magento\Framework\Model\Context                        $context            Magento Context
     * @param \Magento\Framework\Registry                             $registry           Magento Registry
     * @param IndexerRegistry                                         $indexerRegistry    Indexers registry.
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource           Magento Resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection Magento Collection
     * @param array                                                   $data               Magento Data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        IndexerRegistry $indexerRegistry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->indexerRegistry = $indexerRegistry;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Process after save operations
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();

        $this->invalidateIndex();

        return $this;
    }

    /**
     * Process after delete operations
     *
     * @return $this
     */
    public function afterDeleteCommit()
    {
        parent::afterDeleteCommit();

        $this->invalidateIndex();

        return $this;
    }

    /**
     * Retrieve thesaurus name
     *
     * @return string
     */
    public function getName()
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * Set name
     *
     * @param string $name the value to save
     *
     * @return Thesaurus
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, (string) $name);
    }

    /**
     * Get Thesaurus ID
     *
     * @return int|null
     */
    public function getThesaurusId()
    {
        return $this->getId();
    }

    /**
     * Set Thesaurus ID
     *
     * @param int $identifier the value to save
     *
     * @return ThesaurusInterface
     */
    public function setThesaurusId($identifier)
    {
        return $this->setId($identifier);
    }

    /**
     * Retrieve thesaurus type
     *
     * @return string
     */
    public function getType()
    {
        return (string) $this->getData(self::TYPE);
    }

    /**
     * Set type
     *
     * @param string $type the type of thesaurus to save
     *
     * @return ThesaurusInterface
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, (string) $type);
    }

    /**
     * Get store ids
     *
     * @return int[]
     */
    public function getStoreIds()
    {
        if ($this->getStores()) {
            $this->setStoreIds($this->getStores());
        }

        if (empty($this->storeIds)) {
            $this->storeIds = $this->getResource()->getStoreIdsFromThesaurusId($this->getThesaurusId());
        }

        return $this->storeIds;
    }

    /**
     * Set store ids
     *
     * @param int[] $storeIds the store ids
     *
     * @return ThesaurusInterface
     */
    public function setStoreIds($storeIds)
    {
        $this->storeIds = $storeIds;
    }

    /**
     * Get terms data
     *
     * @return array
     */
    public function getTermsData()
    {
        if (empty($this->termsData)) {
            $this->termsData = $this->getResource()->getTermsDataFromThesaurus($this);
        }

        return $this->termsData;
    }

    /**
     * Internal Constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _construct()
    {
        //@codingStandardsIgnoreEnd
        $this->_init('Smile\ElasticSuiteThesaurus\Model\ResourceModel\Thesaurus');
    }

    /**
     * Invalidate Thesaurus index
     *
     * @return $this
     */
    private function invalidateIndex()
    {
        $this->indexerRegistry->get(ThesaurusIndexer::INDEXER_ID)->invalidate();

        return $this;
    }
}
