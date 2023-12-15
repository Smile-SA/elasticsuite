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
namespace Smile\ElasticsuiteCatalog\Model\Category\Indexer;

use Magento\Framework\Search\Request\DimensionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\Category\Indexer\Fulltext\Action\Full;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TranslateInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Categories fulltext indexer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Fulltext implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var string
     */
    const INDEXER_ID = 'elasticsuite_categories_fulltext';

    /**
     * @var IndexerInterface
     */
    private $indexerHandler;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var Full
     */
    private $fullAction;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var TranslateInterface
     */
    private $translator;

    /**
     * @param Full                  $fullAction       The full index action
     * @param IndexerInterface      $indexerHandler   The index handler
     * @param StoreManagerInterface $storeManager     The Store Manager
     * @param DimensionFactory      $dimensionFactory The dimension factory
     * @param ResolverInterface     $localeResolver   The locale resolver
     */
    public function __construct(
        Full $fullAction,
        IndexerInterface $indexerHandler,
        StoreManagerInterface $storeManager,
        DimensionFactory $dimensionFactory,
        ResolverInterface $localeResolver = null,
        TranslateInterface $translator = null
    ) {
        $this->fullAction = $fullAction;
        $this->indexerHandler = $indexerHandler;
        $this->storeManager = $storeManager;
        $this->dimensionFactory = $dimensionFactory;
        $this->localeResolver = $localeResolver ?? ObjectManager::getInstance()->get(ResolverInterface::class);
        $this->translator = $translator ?? ObjectManager::getInstance()->get(TranslateInterface::class);
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids The ids
     *
     * @return void
     */
    public function execute($ids)
    {
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            // load store translation for static attribute options
            $this->localeResolver->emulate($storeId);
            $this->translator->setLocale($this->localeResolver->getLocale())->loadData(null, true);

            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->deleteIndex([$dimension], new ExtendedArray($ids));
            $this->indexerHandler->saveIndex([$dimension], $this->fullAction->rebuildStoreIndex($storeId, $ids));

            $this->localeResolver->revert();
        }

        // reinitialize translation
        $this->translator->setLocale($this->localeResolver->getLocale())->loadData(null, true);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->cleanIndex([$dimension]);
            $this->indexerHandler->saveIndex([$dimension], $this->fullAction->rebuildStoreIndex($storeId));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function executeList(array $categoryIds)
    {
        $this->execute($categoryIds);
    }

    /**
     * {@inheritDoc}
     */
    public function executeRow($categoryId)
    {
        $this->execute([$categoryId]);
    }
}
