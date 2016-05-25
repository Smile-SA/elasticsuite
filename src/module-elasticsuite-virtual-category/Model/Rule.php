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
namespace Smile\ElasticSuiteVirtualCategory\Model;

use Smile\ElasticSuiteCatalogRule\Model\Rule;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Virtual category rule.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Rule extends \Smile\ElasticSuiteCatalogRule\Model\Rule
{
    /**
     * @var \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory
     */
    private $productConditionsFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Model\Context                                                         $context                   Context.
     * @param \Magento\Framework\Registry                                                              $registry                  Registry.
     * @param \Magento\Framework\Data\FormFactory                                                      $formFactory               Form factory.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface                                     $localeDate                Locale date.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory                       $combineConditionsFactory  Search engine rule (combine) condition factory.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory                       $productConditionsFactory  Search engine rule (product) condition factory.
     * @param \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory                                $queryFactory              Search query factory.
     * @param \Magento\Catalog\Model\CategoryFactory                                                   $categoryFactory           Product category factorty.
     * @param \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory $categoryCollectionFactory Virtual categories collection factory.
     * @param array                                                                                    $data                      Additional data.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory $combineConditionsFactory,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory $productConditionsFactory,
        \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->queryFactory              = $queryFactory;
        $this->productConditionsFactory  = $productConditionsFactory;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;

        parent::__construct($context, $registry, $formFactory, $localeDate, $combineConditionsFactory);
    }

    /**
     * Build search query by category.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category           Search category.
     * @param array                                       $excludedCategories Categories that should not be used into search query building.
     *                                                                        Used to avoid infinite recursion while building virtual categories rules.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface
     */
    public function getCategorySearchQuery(\Magento\Catalog\Api\Data\CategoryInterface $category, $excludedCategories = [])
    {
        $excludedCategories[] = $category->getId();

        $queryClauses = [];
        $queryType    = 'must';

        if ((bool) $category->getIsVirtualCategory()) {
            $queryClauses[] = $this->getVirtualCategorySearchQuery($category, $excludedCategories);

            if ($category->getVirtualCategoryRoot()) {
                $parentCategory = $this->getParentCategory($category);

                if ($parentCategory->getVirtualRule()) {
                    $queryClauses[] = $this->getCategorySearchQuery($parentCategory, $excludedCategories);
                }
            }
        } else {
            $queryType    = 'should';
            $queryClauses = array_merge(
                [$this->getNormalCategorySearchQuery($category, $excludedCategories)],
                $this->getCategoryChildrenQueries($category, $excludedCategories)
            );
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, [$queryType => $queryClauses]);
    }

    /**
     * Build search query by category (virtual category).
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category           Search category.
     * @param array                                       $excludedCategories Categories that should not be used into search query building.
     *                                                                        Used to avoid infinite recursion while building virtual categories rules.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface
     */
    private function getVirtualCategorySearchQuery(\Magento\Catalog\Api\Data\CategoryInterface $category, $excludedCategories = [])
    {
        return $category->getVirtualRule()->getConditions()->getSearchQuery($excludedCategories);
    }

    /**
     * Build search query by category (normal category).
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category           Search category.
     * @param array                                       $excludedCategories Categories that should not be used into search query building.
     *                                                                        Used to avoid infinite recursion while building virtual categories rules.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface
     */
    private function getNormalCategorySearchQuery(\Magento\Catalog\Api\Data\CategoryInterface $category, $excludedCategories = [])
    {
        $condition = $this->productConditionsFactory->create(
            ['data' => ['attribute' => 'category_ids', 'operator' => "==", 'value' => $category->getId()]]
        );

        $condition->setRule($this);

        return $condition->getSearchQuery($excludedCategories);
    }

    /**
     * Build search queries list of children categories of a categories.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $parentCategory     Search category.
     * @param array                                       $excludedCategories Categories that should not be used into search query building.
     *                                                                        Used to avoid infinite recursion while building virtual categories rules.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface[]
     */
    private function getCategoryChildrenQueries($parentCategory, $excludedCategories = [])
    {
        $childrenQueries = [];
        $categoryCollection = $this->categoryCollectionFactory->create()
            ->setStoreId($this->getStoreId())
            ->addAttributeToFilter('is_active', true)
            ->addAttributeToFilter('is_virtual_category', true)
            ->addAttributeToFilter('path', ['like' => $parentCategory->getPath(). '/%'])
            ->addAttributeToSelect(['is_virtual_category', 'virtual_category_root', 'virtual_rule']);

        if (!empty($excludedCategories)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $excludedCategories]);
        }

        foreach ($categoryCollection as $category) {
            $childrenQueries[] = $this->getCategorySearchQuery($category, $excludedCategories);
        }

        return $childrenQueries;
    }

    /**
     * Retrive the category used as parent for a given category.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category.
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    private function getParentCategory(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        $parentCategory = $category->getVirtualCategoryRoot();

        if (!is_object($parentCategory)) {
            if (!is_int($parentCategory)) {
                $parentCategory = explode('/', $parentCategory);
                $parentCategory = end($parentCategory);
            }

            $parentCategory = $this->categoryFactory->create()
                ->setStoreId($category->getStoreId())
                ->load($parentCategory);
        }

        return $parentCategory;
    }
}
