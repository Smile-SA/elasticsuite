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
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Listing;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Zend_Db_Expr;

/**
 * Data Provider for UI components based on Sellers
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * Optimizer collection
     *
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * Construct
     *
     * @param string                                                    $name                Component name
     * @param string                                                    $primaryFieldName    Primary field Name
     * @param string                                                    $requestFieldName    Request field name
     * @param CollectionFactory                                         $collectionFactory   The collection factory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]  $addFieldStrategies  Add field Strategy
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies Add filter Strategy
     * @param array                                                     $meta                Component Meta
     * @param array                                                     $data                Component extra data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        $collectionFactory,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection = $collectionFactory->create();

        $this->addFieldStrategies  = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
    }

    /**
     * Add field to select
     *
     * @param string|array $field The field
     * @param string|null  $alias Alias for the field
     *
     * @return void
     */
    public function addField($field, $alias = null)
    {
        if (isset($this->addFieldStrategies[$field])) {
            $this->addFieldStrategies[$field]->addField($this->getCollection(), $field, $alias);

            return ;
        }
        parent::addField($field, $alias);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if (isset($this->addFilterStrategies[$filter->getField()])) {
            $this->addFilterStrategies[$filter->getField()]
                ->addFilter(
                    $this->getCollection(),
                    $filter->getField(),
                    [$filter->getConditionType() => $filter->getValue()]
                );

            return;
        }
        parent::addFilter($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function addOrder($field, $direction)
    {
        // Add custom behavior for boost_weight sorting.
        if ($field == 'boost_weight') {
            // Put optimizer without boost weight at the end of the list.
            parent::addOrder('model', Collection::SORT_ORDER_DESC);

            // Extract boost weight value from config field.
            parent::addOrder(
                new Zend_Db_Expr("SUBSTRING_INDEX(SUBSTRING_INDEX(config, 'constant_score_value\":\"', -1), '\"', 1)"),
                $direction
            );

            return;
        }

        parent::addOrder($field, $direction);
    }
}
