<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Product category filter implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category implements FilterInterface
{
    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
     */
    private $dataProvider;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var boolean
     */
    private $useUrlRewrites;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection|\Magento\Catalog\Model\Category[]
     */
    private $childrenCategories;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                  $filterItemFactory   Filter item factory.
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                     $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder             $itemDataBuilder     Item data builder.
     * @param \Magento\Framework\Escaper                                       $escaper             HTML escaper.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory Data provider.
     * @param boolean                                                          $useUrlRewrites      Uses URLs rewrite for rendering.
     * @param array                                                            $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory,
        $useUrlRewrites = false,
        array $data = []
    ) {
            parent::__construct(
                $filterItemFactory,
                $storeManager,
                $layer,
                $itemDataBuilder,
                $escaper,
                $dataProviderFactory,
                $data
            );

            $this->escaper        = $escaper;
            $this->dataProvider   = $dataProviderFactory->create(['layer' => $this->getLayer()]);
            $this->useUrlRewrites = $useUrlRewrites;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $categoryId = $request->getParam($this->_requestVar) ? : $request->getParam('id');

        if (!empty($categoryId)) {
            $this->dataProvider->setCategoryId($categoryId);

            $category = $this->dataProvider->getCategory();

            $this->applyCategoryFilterToCollection($category);

            if ($request->getParam('id') != $category->getId() && $this->dataProvider->isValid()) {
                $this->getLayer()->getState()->addFilter($this->_createItem($category->getName(), $categoryId));
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addFacetToCollection($config = [])
    {
        $facetField  = $this->getFilterField();
        $facetType   = BucketInterface::TYPE_TERM;
        $facetConfig = ['size' => 0];

        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetField, $facetType, $facetConfig);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _getItemsData()
    {
        $items = [];

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $optionsFacetedData = $productCollection->getFacetedData($this->getFilterField());

        $currentCategory = $this->dataProvider->getCategory();
        $categories = $this->getChildrenCategories();

        if ($currentCategory->getIsActive()) {
            foreach ($categories as $category) {
                if (isset($optionsFacetedData[(int) $category->getId()])) {
                    $productCount = $optionsFacetedData[$category->getId()]['count'];
                    if ($category->getIsActive() && $productCount > 0) {
                        $item = [
                            'label' => $this->escaper->escapeHtml($category->getName()),
                            'value' => $category->getId(),
                            'count' => $optionsFacetedData[$category->getId()]['count'],
                            'url'   => $category->getUrl(),
                        ];

                        $items[] = $item;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Apply the category filter to the layer product collection.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category.
     *
     * @return $this
     */
    protected function applyCategoryFilterToCollection(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        $this->getLayer()->getProductCollection()->addCategoryFilter($category);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _initItems()
    {
        $data = $this->_getItemsData();
        $items = [];
        foreach ($data as $itemData) {
            $item = $this->_createItem($itemData['label'], $itemData['value'], $itemData['count']);
            $items[] = $item;

            if ($this->useUrlRewrites() === true) {
                $item->setUrlRewrite($itemData['url']);
            }
        }
        $this->_items = $items;

        return $this;
    }

    /**
     * Indicates if the filter uses url rewrites or not.
     *
     * @return bool
     */
    protected function useUrlRewrites()
    {
        return $this->useUrlRewrites;
    }

    /**
     * Retrieve currently selected category children categories.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|\Magento\Catalog\Model\Category[]
     */
    protected function getChildrenCategories()
    {
        if ($this->childrenCategories === null) {
            $currentCategory = $this->dataProvider->getCategory();
            $this->childrenCategories = $currentCategory->getChildrenCategories();
        }

        return $this->childrenCategories;
    }

    /**
     * Retrieve ES filter field.
     *
     * @return string
     */
    protected function getFilterField()
    {
        return 'category.category_id';
    }

    /**
     * Category data provider.
     *
     * @return \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
     */
    protected function getDataProvider()
    {
        return $this->dataProvider;
    }
}
