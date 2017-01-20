<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Category;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalog\Helper\Autocomplete as ConfigurationHelper;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Fulltext\CollectionFactory as CategoryCollectionFactory;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;

/**
 * Catalog category autocomplete data provider.
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCatalog
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
     * @var TermDataProvider
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
     * @param TermDataProvider          $termDataProvider          Search terms suggester.
     * @param CategoryCollectionFactory $categoryCollectionFactory Category collection factory.
     * @param ConfigurationHelper       $configurationHelper       Autocomplete configuration helper.
     * @param string                    $type                      Autocomplete provider type.
     */
    public function __construct(
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        TermDataProvider $termDataProvider,
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
                    $result[] = $this->itemFactory->create(['category' => $category, 'type' => $this->getType()]);
                }
            }
        }

        return $result;
    }

    /**
     * List of search terms suggested by the search terms data provider.
     *
     * @return array
     */
    private function getSuggestedTerms()
    {
        $terms = array_map(
            function (\Magento\Search\Model\Autocomplete\Item $termItem) {
                return $termItem->getTitle();
            },
            $this->termDataProvider->getItems()
        );

        return $terms;
    }

    /**
     * Suggested categories collection.
     * Returns null if no suggested search terms.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Fulltext\Collection|null
     */
    private function getCategoryCollection()
    {
        $categoryCollection = null;

        $suggestedTerms = $this->getSuggestedTerms();
        $terms          = [$this->queryFactory->get()->getQueryText()];

        if (!empty($suggestedTerms)) {
            $terms = array_merge($terms, $suggestedTerms);
        }

        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addSearchFilter($terms);
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
