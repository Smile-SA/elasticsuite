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

namespace Smile\ElasticsuiteCatalog\Block\Plugin;

use Magento\CatalogSearch\Block\Result;
use Magento\CatalogSearch\Helper\Data;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Search\Query as QueryResource;

/**
 * Block plugin that ensures search result count saved is correct and is_spellchecked is filled into reports.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ResultPlugin
{
    /**
     * Catalog search data
     *
     * @var Data
     */
    protected $catalogSearchData;

    /**
     * @var Layer
     */
    protected $layer;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var QueryResource
     */
    protected $queryResource;

    /**
     * @var integer
     */
    protected $resultCount = null;

    /**
     * Constructor.
     *
     * @param Resolver      $layerResolver     Layer.
     * @param Data          $catalogSearchData Catalog search helper.
     * @param QueryFactory  $queryFactory      Search query factory.
     * @param QueryResource $queryResource     Search query resource.
     */
    public function __construct(
        Resolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        QueryResource $queryResource
    ) {
        $this->layer             = $layerResolver->get();
        $this->catalogSearchData = $catalogSearchData;
        $this->queryFactory      = $queryFactory;
        $this->queryResource     = $queryResource;
    }

    /**
     * Append the fuzziness alert message + save the search result count.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\CatalogSearch\Block\Result $resultBlock Result block.
     * @param \Closure                            $proceed     Original method.
     *
     * @return string[]
     */
    public function aroundGetNoteMessages(Result $resultBlock, \Closure $proceed)
    {
        $messages = $proceed();
        $query    = $this->queryFactory->get();

        $query->setNumResults($this->resultCount);
        $query->setIsSpellchecked(false);
        $isSpellcheck = $resultBlock->getListBlock()->getLoadedProductCollection()->isSpellchecked();

        if ($isSpellcheck && $this->resultCount > 0) {
            $messages[] = __(
                "No search results for: <b>'%1'</b>. We propose you approaching results.",
                $this->catalogSearchData->getEscapedQueryText()
            );
            $query->setIsSpellchecked(true);
        }

        if ($this->canSaveQuery()) {
            $this->queryResource->saveSearchResults($query);
        }

        return $messages;
    }

    /**
     * Avoid the search result count to be saved by the original method.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\CatalogSearch\Block\Result $resultBlock Result block.
     * @param \Closure                            $proceed     Original method.
     *
     * @return integer
     */
    public function aroundGetResultCount(Result $resultBlock, \Closure $proceed)
    {
        if ($this->resultCount === null) {
            $size = $resultBlock->getListBlock()->getLoadedProductCollection()->getSize();
            $this->resultCount = $size;
        }

        return $this->resultCount;
    }

    /**
     * Change default behavior of the search result block.
     * Order has to be set to ASC and not DESC.
     *
     * @param \Magento\CatalogSearch\Block\Result $resultBlock Result block.
     * @param \Closure                            $proceed     Original method.
     *
     * @return \Magento\CatalogSearch\Block\Result
     */
    public function aroundSetListOrders(Result $resultBlock, \Closure $proceed)
    {
        $proceed();
        $resultBlock->getListBlock()->setDefaultDirection('asc');

        return $resultBlock;
    }

    /**
     * Indicates if the search query should be save or not.
     *
     * @return boolean
     */
    private function canSaveQuery()
    {
        return empty($this->layer->getState()->getFilters());
    }
}
