<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel;

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogRule\Model\RuleFactory;

/**
 * Optimizer Resource Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Optimizer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context        Context.
     * @param RuleFactory                                       $ruleFactory    Rule factory.
     * @param \Magento\Framework\Serialize\SerializerInterface  $serializer     Serializer.
     * @param string                                            $connectionName Connection name.
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        RuleFactory $ruleFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->ruleFactory = $ruleFactory;
        $this->serializer  = $serializer;
    }

    /**
     * Retrieve Search Containers for a given optimizer.
     *
     * @param int $optimizerId The optimizer Id
     *
     * @return array
     */
    public function getSearchContainersFromOptimizerId($optimizerId)
    {
        $connection = $this->getConnection();

        $select = $connection->select();

        $select->from(
            $this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER),
            [OptimizerInterface::SEARCH_CONTAINER, 'apply_to']
        )->where(OptimizerInterface::OPTIMIZER_ID . ' = ?', (int) $optimizerId);

        return $connection->fetchPairs($select);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init(OptimizerInterface::TABLE_NAME, OptimizerInterface::OPTIMIZER_ID);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);

        $this->saveSearchContainerRelation($object);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $searchContainers = $this->getSearchContainersFromOptimizerId($object->getId());
            $object->setSearchContainers($searchContainers);
        }

         /* Using getter to force unserialize*/
        $object->getConfig();
        $object->getRuleCondition();

        return parent::_afterLoad($object);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if (is_array($object->getConfig())) {
            $object->setConfig($this->serializer->serialize($object->getConfig()));
        }

        $rule = $this->ruleFactory->create();
        $ruleCondition = $object->getRuleCondition();

        if (is_object($ruleCondition)) {
            $rule = $ruleCondition;
        } elseif (is_array($ruleCondition)) {
            $rule->getConditions()->loadArray($ruleCondition);
        }
        $object->setRuleCondition($this->serializer->serialize($rule->getConditions()->asArray()));

        return parent::_beforeSave($object);
    }

    /**
     * Saves relation between optimizer and search container
     *
     * @param \Magento\Framework\Model\AbstractModel $object Optimizer to save
     *
     * @return void
     */
    private function saveSearchContainerRelation(\Magento\Framework\Model\AbstractModel $object)
    {
        $searchContainers = $object->getSearchContainer();

        if (is_array($searchContainers) && (count($searchContainers) > 0)) {
            $searchContainerLinks = [];
            $deleteCondition = OptimizerInterface::OPTIMIZER_ID . " = " . $object->getId();

            foreach ($searchContainers as $searchContainer) {
                $searchContainerName = (string) $searchContainer;
                // Treat autocomplete apply_to like the quick search.
                if ($searchContainerName === 'catalog_product_autocomplete') {
                    $searchContainerName = 'quick_search_container';
                }
                $searchContainerData = $object->getData($searchContainerName);
                $applyTo = is_array($searchContainerData) ? ((bool) ($searchContainerData['apply_to'] ?? false)) : false;
                $searchContainerLinks[(string) $searchContainer] = [
                    OptimizerInterface::OPTIMIZER_ID     => (int) $object->getId(),
                    OptimizerInterface::SEARCH_CONTAINER => (string) $searchContainer,
                    'apply_to'                           => (int) $applyTo,
                ];
            }

            $this->getConnection()->delete($this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER), $deleteCondition);
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER),
                $searchContainerLinks
            );
        }
    }
}
