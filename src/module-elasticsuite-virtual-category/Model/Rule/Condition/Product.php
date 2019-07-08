<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition;

use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttributesProvider;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product search rule condition.
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
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    private $categoryRepository;

    /**
     * Constructor.
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Rule\Model\Condition\Context                                     $context                   Rule context.
     * @param \Magento\Backend\Helper\Data                                              $backendData               Admin helper.
     * @param \Magento\Eav\Model\Config                                                 $config                    EAV config.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList             Product search rule
     *                                                                                                             attribute list.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder  $queryBuilder              Product search rule
     *                                                                                                             query builder.
     * @param \Magento\Catalog\Model\ProductFactory                                     $productFactory            Product factory.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                           $productRepository         Product repository.
     * @param \Magento\Catalog\Model\ResourceModel\Product                              $productResource           Product resource model.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection          $attrSetCollection         Attribute set collection.
     * @param \Magento\Framework\Locale\FormatInterface                                 $localeFormat              Locale format.
     * @param SpecialAttributesProvider                                                 $specialAttributesProvider Special attributes
     *                                                                                                             provider.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                 $queryFactory              Search query factory.
     * @param \Magento\Catalog\Model\CategoryRepository                                 $categoryRepository        Category Repository
     * @param array                                                                     $data                      Additional data.
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
        SpecialAttributesProvider $specialAttributesProvider,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $backendData,
            $config,
            $attributeList,
            $queryBuilder,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $specialAttributesProvider,
            $data
        );
        $this->queryFactory = $queryFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Build a search query for the current rule.
     *
     * @param array    $excludedCategories  Categories excluded of query building (avoid infinite recursion).
     * @param int|null $virtualCategoryRoot Category root for Virtual Category.
     *
     * @return QueryInterface|null
     */
    public function getSearchQuery($excludedCategories = [], $virtualCategoryRoot = null): ?QueryInterface
    {
        $searchQuery = parent::getSearchQuery();

        if ($this->getAttribute() === 'category_ids') {
            $searchQuery = $this->getCategorySearchQuery($excludedCategories, $virtualCategoryRoot);
        }

        return $searchQuery;
    }

    /**
     * Retrieve value element chooser URL
     *
     * @return string
     */
    public function getValueElementChooserUrl(): string
    {
        $url = parent::getValueElementChooserUrl();

        if ($this->getAttribute() === 'category_ids') {
            $url = $this->getCategoryChooserUrl();
        }

        return $url;
    }

    /**
     * Retrieve a query used to apply category filter rule.
     *
     * @param array    $excludedCategories  Category excluded from the loading (avoid infinite loop in query
     *                                      building when circular references are present).
     * @param int|null $virtualCategoryRoot Category root for Virtual Category.
     *
     * @return QueryInterface|null
     */
    private function getCategorySearchQuery($excludedCategories, $virtualCategoryRoot = null): ?QueryInterface
    {
        $categoryIds = [];
        $subQueries  = [];
        $valueArray = explode(',', str_replace(' ', '', $this->getValue()));

        if ($this->getOperator() === '!()') {
            $categoryIds = array_diff(
                $this->categoryRepository->get($virtualCategoryRoot)->getChildrenCategories()->getAllIds(),
                $valueArray
            );
        }
        if ($this->getOperator() !== '!()') {
            $categoryIds = array_diff($valueArray, $excludedCategories);
        }

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

    /**
     * Get category chooser Url.
     * Might be contextualised according to current object data, if needed.
     * This will allow the Ajax call to the chooser to transfer current context (especially category_id).
     *
     * @return string
     */
    private function getCategoryChooserUrl(): string
    {
        $url = 'catalog_rule/promo_widget/chooser/attribute/' . $this->getAttribute();

        $chooserUrlParams = [];
        if ($this->getJsFormObject()) {
            $chooserUrlParams['form'] = $this->getJsFormObject();
        }

        $urlParams = $this->getUrlParams();
        if ($urlParams && is_array($urlParams) && isset($urlParams['category_id'])) {
            $chooserUrlParams['category_id'] = $urlParams['category_id'];
        }

        $url = $this->_backendData->getUrl($url, $chooserUrlParams);

        return $url;
    }
}
