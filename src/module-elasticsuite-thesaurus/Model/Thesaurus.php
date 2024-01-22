<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;
use Smile\ElasticsuiteThesaurus\Model\Indexer\Thesaurus as ThesaurusIndexer;

/**
 * Thesaurus Model
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Thesaurus extends \Magento\Framework\Model\AbstractModel implements ThesaurusInterface
{
    /**
     * Name of the Thesaurus Expanded Terms Mysql Table
     */
    const THESAURUS_EXPANDED_TERMS_TABLE_NAME = 'smile_elasticsuite_thesaurus_expanded_terms';

    /**
     * Name of the Thesaurus Reference Terms Mysql Table
     */
    const THESAURUS_REFERENCE_TERMS_TABLE_NAME = 'smile_elasticsuite_thesaurus_reference_terms';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smile_elasticsuite_thesaurus';

    /**
     * Parameter name in event
     * In observer method you can use $observer->getEvent()->getThesaurus() in this case
     *
     * @var string
     */
    protected $_eventObject = 'thesaurus';

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * Thesaurus Factory
     *
     * @var ThesaurusFactory
     */
    protected $thesaurusFactory;

    /**
     * Resource Connection
     *
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var array The store ids of this thesaurus
     */
    private $storeIds = [];

    /**
     * @var array The term data of this thesaurus
     */
    private $termsData;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Message manager
     *
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * PHP constructor
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Framework\Model\Context                        $context            Magento Context
     * @param \Magento\Framework\Registry                             $registry           Magento Registry
     * @param IndexerRegistry                                         $indexerRegistry    Indexers registry
     * @param ThesaurusFactory                                        $thesaurusFactory   Thesaurus Factory
     * @param ResourceConnection                                      $resourceConnection Resource Connection
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager       Store Manager
     * @param ManagerInterface                                        $messageManager     Message Manager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource           Magento Resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection Magento Collection
     * @param array                                                   $data               Magento Data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        IndexerRegistry $indexerRegistry,
        ThesaurusFactory $thesaurusFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->indexerRegistry    = $indexerRegistry;
        $this->thesaurusFactory   = $thesaurusFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager       = $storeManager;
        $this->messageManager     = $messageManager;

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

        $this->checkThesaurusTerms();
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
        if ($this->storeManager->isSingleStoreMode()) {
            $this->storeIds = [$this->storeManager->getStore(true)->getId()];
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
        $this->setData('store_id', $storeIds);
        $this->storeIds = $storeIds;

        return $this;
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
     * Get Thesaurus status
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Set Thesaurus status
     *
     * @param bool $status The thesaurus status
     *
     * @return ThesaurusInterface
     */
    public function setIsActive($status)
    {
        return $this->setData(self::IS_ACTIVE, (bool) $status);
    }

    /**
     * Check for existing terms in other thesaurus
     *
     * @return void
     */
    public function checkThesaurusTerms()
    {
        $termsData = $this->getTermsData();

        $terms = [];
        foreach ($termsData as $termData) {
            if (isset($termData['reference_term']) && !empty($termData['reference_term'])) {
                $terms[] = $termData['reference_term'];
            }

            $terms = array_merge($terms, explode(',', $termData['values']));
        }

        $connection = $this->resourceConnection->getConnection();
        $expandedTableName = $this->resourceConnection->getTableName(self::THESAURUS_EXPANDED_TERMS_TABLE_NAME);
        $referenceTableName = $this->resourceConnection->getTableName(self::THESAURUS_REFERENCE_TERMS_TABLE_NAME);

        $selectExpanded = $connection->select()
            ->from(['expanded' => $expandedTableName], ['term', 'thesaurus_id', 'count' => 'COUNT(*)'])
            ->where('expanded.term IN (?)', $terms)
            ->where('expanded.thesaurus_id != ?', $this->getId())
            ->group(['term', 'thesaurus_id'])
            ->order('expanded.term ASC');

        $selectReference = $connection->select()
            ->from(['reference' => $referenceTableName], ['term', 'thesaurus_id', 'count' => 'COUNT(*)'])
            ->where('reference.term IN (?)', $terms)
            ->where('reference.thesaurus_id != ?', $this->getId())
            ->group(['term', 'thesaurus_id']);

        $resultExpanded = $connection->fetchAll($selectExpanded);
        $resultReference = $connection->fetchAll($selectReference);

        $result = array_merge($resultReference, $resultExpanded);

        foreach ($result as $row) {
            if ($row['count'] > 0) {
                $existingThesaurusId = $row['thesaurus_id'];
                $existingThesaurusName = $this->getThesaurusNameById($existingThesaurusId);

                $message = __(
                    'The term "<strong>%1</strong>" is already existing in the <strong>%2</strong> thesaurus.',
                    $row['term'],
                    $existingThesaurusName
                );
                $this->messageManager->addWarning($message);
            }
        }
    }

    /**
     * Internal Constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init('Smile\ElasticsuiteThesaurus\Model\ResourceModel\Thesaurus');
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

    /**
     * Get the name of the thesaurus by ID
     *
     * @param int $thesaurusId Thesaurus ID
     * @return string
     */
    private function getThesaurusNameById($thesaurusId)
    {
        $thesaurus = $this->thesaurusFactory->create()->load($thesaurusId);

        return $thesaurus->getName();
    }
}
