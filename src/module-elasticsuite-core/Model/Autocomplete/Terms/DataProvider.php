<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Autocomplete\Terms;

use Magento\Search\Model\ResourceModel\Query\Collection;
use Magento\Search\Model\QueryFactory;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Smile\ElasticsuiteCore\Helper\Autocomplete as ConfigurationHelper;

/**
 * Popular search terms data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Autocomplete type
     */
    const AUTOCOMPLETE_TYPE = "term";

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
     * @var ConfigurationHelper
     */
    protected $configurationHelper;

    /**
     *
     * @var \Magento\Search\Model\Autocomplete\Item[]|null
     */
    private $items;

    /**
     * @var string
     */
    private $type;

    /**
     * Constructor.
     *
     * @param QueryFactory        $queryFactory        Search query text factory.
     * @param ItemFactory         $itemFactory         Suggest terms item facory.
     * @param ConfigurationHelper $configurationHelper Autocomplete configuration helper.
     * @param string              $type                Autocomplete items type.
     */
    public function __construct(
        QueryFactory $queryFactory,
        ItemFactory $itemFactory,
        ConfigurationHelper $configurationHelper,
        $type = self::AUTOCOMPLETE_TYPE
    ) {
        $this->queryFactory        = $queryFactory;
        $this->itemFactory         = $itemFactory;
        $this->configurationHelper = $configurationHelper;
        $this->type                = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems()
    {
        if ($this->items === null) {
            $collection = $this->getSuggestCollection();
            $this->items = [];

            if ($this->configurationHelper->isEnabled($this->getType())) {
                foreach ($collection as $item) {
                    $resultItem = $this->itemFactory->create([
                        'title' => $item->getQueryText(),
                        'num_results' => $item->getNumResults(),
                        'type'        => $this->getType(),
                    ]);
                    $this->items[] = $resultItem;
                }
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
        return $this->configurationHelper->getMaxSize($this->getType());
    }
}
