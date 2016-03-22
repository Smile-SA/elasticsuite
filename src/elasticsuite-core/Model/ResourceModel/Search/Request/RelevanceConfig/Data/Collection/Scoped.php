<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data\Collection;

use Smile\ElasticSuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Scoped configuration resource collection
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Scoped extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Scope to filter by
     *
     * @var string
     */
    protected $scope;

    /**
     * Scope code to filter by
     *
     * @var string
     */
    protected $scopeCode;

    /**
     * @param EntityFactory          $entityFactory The entity factory
     * @param LoggerInterface        $logger        The internal logger
     * @param FetchStrategyInterface $fetchStrategy The fetch strategy
     * @param ManagerInterface       $eventManager  The event manager
     * @param Data                   $resource      The resource model being used
     * @param string                 $scope         The configuration scope
     * @param mixed                  $connection    Database Connection
     * @param mixed                  $scopeCode     The scope code
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Data $resource,
        $scope,
        AdapterInterface $connection = null,
        $scopeCode = null
    ) {
        $this->scope = $scope;
        $this->scopeCode = $scopeCode;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * Initialize select
     *
     * @return $this
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _initSelect()
    {
        // @codingStandardsIgnoreEnd
        parent::_initSelect();
        $this->addFieldToSelect(['path', 'value'])->addFieldToFilter('scope', $this->scope);

        if ($this->scopeCode !== null) {
            $this->addFieldToFilter('scope_code', $this->scopeCode);
        }

        return $this;
    }
}
