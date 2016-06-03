<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteVirtualCategory\Model\Rule\Condition;

use Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Product search rule condition.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Product extends \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product
{
    /**
     * @var \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Rule\Model\Condition\Context                                     $context           Rule context.
     * @param \Magento\Backend\Helper\Data                                              $backendData       Admin helper.
     * @param \Magento\Eav\Model\Config                                                 $config            EAV config.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList     Product search rule attribute list.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder  $queryBuilder      Product search rule query builder.
     * @param \Magento\Catalog\Model\ProductFactory                                     $productFactory    Product factory.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                           $productRepository Product repository.
     * @param \Magento\Catalog\Model\ResourceModel\Product                              $productResource   Product resource model.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection          $attrSetCollection Attribute set collection.
     * @param \Magento\Framework\Locale\FormatInterface                                 $localeFormat      Locale format.
     * @param \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory                 $queryFactory      Search query factory.
     * @param array                                                                     $data              Additional data.
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder $queryBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct($context, $backendData, $config, $attributeList, $queryBuilder, $productFactory, $productRepository, $productResource, $attrSetCollection, $localeFormat, $data);
        $this->queryFactory = $queryFactory;
    }

    /**
     * Build a search query for the current rule.
     *
     * @param array $excludedCategories Categories excluded of query building (avoid infinite recursion).
     *
     * @return QueryInterface
     */
    public function getSearchQuery($excludedCategories = [])
    {
        $searchQuery = parent::getSearchQuery();

        if ($this->getAttribute() === 'category_ids') {
            $searchQuery = $this->getCategorySearchQuery($excludedCategories);
        }

        return $searchQuery;
    }

    /**
     * Retrieve a query used to apply category filter rule.
     *
     * @param array $excludedCategories Category excluded from the loading (avoid infinite loop in query building when circular references are present).
     *
     * @return QueryInterface
     */
    private function getCategorySearchQuery($excludedCategories)
    {
        $categoryIds = array_diff(explode(',', $this->getValue()), $excludedCategories);
        $subQueries  = [];

        foreach ($categoryIds as $categoryId) {
            $subQueries[] = $this->getRule()->getCategorySearchQuery($categoryId, $excludedCategories);
        }

        $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $subQueries]);

        if (count($subQueries) === 1) {
            $query = current($subQueries);
        }

        return $query;
    }
}
