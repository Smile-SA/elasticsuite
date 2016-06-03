<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Model\Layer\Filter;

use Smile\ElasticSuiteCore\Search\Request\BucketInterface;

/**
 * Product category filter implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category
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
        $categoryId = $request->getParam($this->_requestVar) ?: $request->getParam('id');

        if (!empty($categoryId)) {
            $this->dataProvider->setCategoryId($categoryId);

            $category = $this->dataProvider->getCategory();

            $this->getLayer()->getProductCollection()->addCategoryFilter($category);

            if ($request->getParam('id') != $category->getId() && $this->dataProvider->isValid()) {
                $this->getLayer()->getState()->addFilter($this->_createItem($category->getName(), $categoryId));
            }
        }

        return $this;
    }

    /**
     * Append the facet to the product collection.
     *
     * @return \Smile\ElasticSuiteCatalog\Model\Layer\Filter\Category
     */
    public function addFacetToCollection()
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
        $optionsFacetedData = $productCollection->getFacetedData('category.category_id');
        $currentCategory = $this->dataProvider->getCategory();

        $categories = $currentCategory->getChildrenCategories();

        if ($currentCategory->getIsActive()) {
            foreach ($categories as $category) {
                if ($category->getIsActive() && isset($optionsFacetedData[(int) $category->getId()])) {
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

        return $items;
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

            if ($this->useUrlRewrites === true) {
                $item->setUrlRewrite($itemData['url']);
            }
        }
        $this->_items = $items;

        return $this;
    }

    /**
     * Retrieve ES filter field.
     *
     * @return string
     */
    private function getFilterField()
    {
        return 'category.category_id';
    }
}
