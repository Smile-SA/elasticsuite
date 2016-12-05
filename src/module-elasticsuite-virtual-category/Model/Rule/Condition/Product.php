<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product search rule condition.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Product extends \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
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
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList     Product search rule attribute list.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder  $queryBuilder      Product search rule query builder.
     * @param \Magento\Catalog\Model\ProductFactory                                     $productFactory    Product factory.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                           $productRepository Product repository.
     * @param \Magento\Catalog\Model\ResourceModel\Product                              $productResource   Product resource model.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection          $attrSetCollection Attribute set collection.
     * @param \Magento\Framework\Locale\FormatInterface                                 $localeFormat      Locale format.
     * @param \Magento\Config\Model\Config\Source\Yesno                                 $booleanSource     Data source for boolean select.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                 $queryFactory      Search query factory.
     * @param array                                                                     $data              Additional data.
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList,
        \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder $queryBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Config\Model\Config\Source\Yesno $booleanSource,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct($context, $backendData, $config, $attributeList, $queryBuilder, $productFactory, $productRepository, $productResource, $attrSetCollection, $localeFormat, $booleanSource, $data);
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
            $subQuery = $this->getRule()->getCategorySearchQuery($categoryId, $excludedCategories);
            if ($subQuery !== null) {
                $subQueries[] = $subQuery;
            }
        }

        $query = null;

        if (count($subQueries) === 1) {
            $query = current($subQueries);
        } elseif (count($subQueries) > 1) {
            $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $subQueries]);
        }

        return $query;
    }
}
