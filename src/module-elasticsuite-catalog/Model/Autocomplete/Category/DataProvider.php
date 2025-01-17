<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Category;

use Magento\Catalog\Model\Category;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalog\Helper\Autocomplete as ConfigurationHelper;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Fulltext\CollectionFactory as CategoryCollectionFactory;
use Smile\ElasticsuiteCore\Model\Autocomplete\SuggestedTermsProvider;

/**
 * Catalog category autocomplete data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Autocomplete type
     */
    const AUTOCOMPLETE_TYPE = "category";

    /**
     * Autocomplete result item factory
     *
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * Query factory
     *
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var SuggestedTermsProvider
     */
    protected $termDataProvider;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var ConfigurationHelper
     */
    protected $configurationHelper;

    /**
     * @var string Autocomplete result type
     */
    private $type;

    /**
     * Constructor.
     *
     * @param ItemFactory               $itemFactory               Suggest item factory.
     * @param QueryFactory              $queryFactory              Search query factory.
     * @param SuggestedTermsProvider    $termDataProvider          Search terms suggester.
     * @param CategoryCollectionFactory $categoryCollectionFactory Category collection factory.
     * @param ConfigurationHelper       $configurationHelper       Autocomplete configuration helper.
     * @param string                    $type                      Autocomplete provider type.
     */
    public function __construct(
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        SuggestedTermsProvider $termDataProvider,
        CategoryCollectionFactory $categoryCollectionFactory,
        ConfigurationHelper $configurationHelper,
        $type = self::AUTOCOMPLETE_TYPE
    ) {
        $this->itemFactory               = $itemFactory;
        $this->queryFactory              = $queryFactory;
        $this->termDataProvider          = $termDataProvider;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->configurationHelper       = $configurationHelper;
        $this->type                      = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getItems()
    {
        $result = [];

        if ($this->configurationHelper->isEnabled($this->getType())) {
            $categoryCollection = $this->getCategoryCollection();
            if ($categoryCollection) {
                foreach ($categoryCollection as $category) {
                    if (!$this->isCategoryAvailable($category)) {
                        continue;
                    }

                    $result[] = $this->itemFactory->create([
                        'category' => $category,
                        'type'     => $this->getType(),
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Filter disabled categories
     *
     * @param Category $category Filterable category
     *
     * @return bool
     */
    private function isCategoryAvailable(Category $category): bool
    {
        return $category->getIsActive()
            && !$category->getIsHidden()
            && $category->isInRootCategoryList()
        ;
    }

    /**
     * Suggested categories collection.
     * Returns null if no suggested search terms.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Fulltext\Collection|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCategoryCollection()
    {
        $categoryCollection = null;

        $suggestedTerms = $this->termDataProvider->getSuggestedTerms();
        $terms          = [$this->queryFactory->get()->getQueryText()];

        if (!empty($suggestedTerms)) {
            $terms = array_merge($terms, $suggestedTerms);
        }

        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect("is_active");
        $categoryCollection->addFieldToFilter("is_displayed_in_autocomplete", true);
        $categoryCollection->setSearchQuery($terms);
        $categoryCollection->setPageSize($this->getResultsPageSize());

        return $categoryCollection;
    }

    /**
     * Retrieve number of categories to display in autocomplete results
     *
     * @return int
     */
    private function getResultsPageSize()
    {
        return $this->configurationHelper->getMaxSize($this->getType());
    }
}
