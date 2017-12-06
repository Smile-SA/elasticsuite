<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source\DisplayMode;

/**
 * Filterable Attribute Collection.
 * Can be retrieved for a given category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
{
    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory    Entity Factory
     * @param \Psr\Log\LoggerInterface                                     $logger           Logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy    Fetch Strategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager     Event Manager
     * @param \Magento\Eav\Model\Config                                    $eavConfig        EAV Config
     * @param \Magento\Eav\Model\EntityFactory                             $eavEntityFactory EAV entity Factory
     * @param CategoryInterface                                            $category         Category
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection       Connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null    $resource         Resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        CategoryInterface $category,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->category = $category;

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

        $positionExpr    = sprintf('COALESCE(fal.position, NULLIF(additional_table.position, 0), %s)', PHP_INT_MAX);
        $displayModeExpr = sprintf('COALESCE(fal.display_mode, %s)', DisplayMode::AUTO_DISPLAYED);

        $this->getSelect()->columns([
            'position'     => new \Zend_Db_Expr($positionExpr),
            'display_mode' => new \Zend_Db_Expr($displayModeExpr),
        ]);

        $joinCondition = [
            'fal.attribute_id = main_table.attribute_id',
            $this->getConnection()->quoteInto('fal.entity_id = ?', (int) $this->category->getId()),
        ];

        $this->joinLeft(
            ['fal' => $this->getTable('smile_elasticsuitecatalog_category_filterable_attribute')],
            new \Zend_Db_Expr(implode(' AND ', $joinCondition)),
            []
        );

        $this->addSetInfo(true);
        $this->addIsFilterableFilter();

        return $this;
    }
}
