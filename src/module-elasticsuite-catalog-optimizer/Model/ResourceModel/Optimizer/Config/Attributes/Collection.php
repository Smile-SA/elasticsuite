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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Config\Attributes;

/**
 * Collection of attributes that can be used to create linear-based optimizers.
 * Basically only integer/decimal attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
{
    /**
     * @var array
     */
    private $defaultAvailableBackendTypes = ['decimal'];

    /**
     * @var array
     */
    private $availableBackendTypes = [];

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory         Entity Factory
     * @param \Psr\Log\LoggerInterface                                     $logger                Logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy         Fetch Strategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager          Event Manager
     * @param \Magento\Eav\Model\Config                                    $eavConfig             EAV Config
     * @param \Magento\Eav\Model\EntityFactory                             $eavEntityFactory      EAV Entity Factory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection            Connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null    $resource              Resource Connection
     * @param array                                                        $availableBackendTypes Available Backend Types.
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        $availableBackendTypes = []
    ) {
        $this->availableBackendTypes = array_merge($this->defaultAvailableBackendTypes, $availableBackendTypes);

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $eavEntityFactory,
            $connection,
            $resource
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addFieldToFilter(
            ['is_searchable', 'is_filterable', 'is_filterable_in_search', 'is_used_for_promo_rules', 'used_for_sort_by'],
            [true, [1, 2], true, true, true]
        );

        $conditions = [
            $this->_getConditionSql('backend_type', ['in' => $this->availableBackendTypes]),
            "(backend_type = 'varchar' AND frontend_class = 'validate-number')",
            "(backend_type = 'varchar' AND frontend_class = 'validate-digits')",
        ];

        $this->getSelect()->where(implode(' OR ', $conditions));

        return $this;
    }
}
