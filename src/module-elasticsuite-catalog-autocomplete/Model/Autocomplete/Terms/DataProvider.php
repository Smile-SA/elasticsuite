<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalogAutocomplete
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalogAutocomplete\Model\Autocomplete\Terms;

use Magento\Search\Model\ResourceModel\Query\Collection;
use Magento\Search\Model\QueryFactory;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Smile\ElasticSuiteCatalogAutocomplete\Helper\Configuration as ConfigurationHelper;

/**
 * Popular search terms data provider.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogAutocomplete
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Query factory
     *
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * Autocomplete result item factory
     *
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     *
     * @var \Magento\Search\Model\Autocomplete\Item[]
     */
    private $items;

    /**
     * @var ConfigurationHelper
     */
    protected $configurationHelper;

    /**
     * Constructor.
     *
     * @param QueryFactory        $queryFactory        Search query text factory.
     * @param ItemFactory         $itemFactory         Suggest terms item facory.
     * @param ConfigurationHelper $configurationHelper Autocomplete configuration helper.
     */
    public function __construct(
        QueryFactory $queryFactory,
        ItemFactory $itemFactory,
        ConfigurationHelper $configurationHelper
    ) {
        $this->queryFactory        = $queryFactory;
        $this->itemFactory         = $itemFactory;
        $this->configurationHelper = $configurationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems()
    {
        if ($this->items === null) {
            $collection = $this->getSuggestCollection();
            $this->items = [];
            foreach ($collection as $item) {
                $resultItem = $this->itemFactory->create([
                    'title' => $item->getQueryText(),
                    'num_results' => $item->getNumResults(),
                    'type'        => 'term',
                ]);
                $this->items[] = $resultItem;
            }
        }

        return $this->items;
    }

    /**
     * Retrieve suggest collection for query
     *
     * @return Collection
     */
    private function getSuggestCollection()
    {
        $queryCollection = $this->queryFactory->get()->getSuggestCollection();
        $queryCollection->addFieldToFilter('is_spellchecked', 'false')->setPageSize($this->getResultsPageSize());

        return $queryCollection;
    }

    /**
     * Retrieve number of products to display in autocomplete results
     *
     * @return int
     */
    private function getResultsPageSize()
    {
        return $this->configurationHelper->getTermsMaxSize();
    }
}
