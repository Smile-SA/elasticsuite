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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Config\Attributes;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\EntityFactory as EavEntityFactory;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory as CollectionEntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Psr\Log\LoggerInterface;

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
     * @var array
     */
    private $nestedFieldAttributes = [];

    /**
     * Collection constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param CollectionEntityFactory $entityFactory         Entity Factory.
     * @param LoggerInterface         $logger                Logger.
     * @param FetchStrategyInterface  $fetchStrategy         Fetch Strategy.
     * @param ManagerInterface        $eventManager          Event Manager.
     * @param Config                  $eavConfig             EAV Config.
     * @param EavEntityFactory        $eavEntityFactory      EAV Entity Factory.
     * @param AdapterInterface|null   $connection            Connection.
     * @param AbstractDb|null         $resource              Resource Connection.
     * @param array                   $availableBackendTypes Available Backend Types.
     * @param array                   $nestedFieldAttributes Attributes represented by a nested field in the index.
     */
    public function __construct(
        CollectionEntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Config $eavConfig,
        EavEntityFactory $eavEntityFactory,
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null,
        $availableBackendTypes = [],
        $nestedFieldAttributes = []
    ) {

        $this->availableBackendTypes = array_merge($this->defaultAvailableBackendTypes, $availableBackendTypes);
        $this->nestedFieldAttributes = $nestedFieldAttributes;

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

        if (!empty($this->nestedFieldAttributes)) {
            $this->addFieldToFilter('attribute_code', ['nin' => $this->nestedFieldAttributes]);
        }

        return $this;
    }
}
