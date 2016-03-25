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

namespace Smile\ElasticSuiteCatalog\Block\Plugin;

use Magento\CatalogSearch\Block\Result;
use Magento\CatalogSearch\Helper\Data;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Search\Query as QueryResource;

/**
 * Block plugin that ensures search result count saved is correct and is_spellchecked is filled into reports.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
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
     * @param Result   $subject Search result block.
     * @param \Closure $proceed Original method.
     *
     * @return string[]
     */
    public function aroundGetNoteMessages(Result $subject, \Closure $proceed)
    {
        $messages = $proceed();
        $query    = $this->queryFactory->get();

        $query->setNumResults($this->resultCount);
        $query->setIsSpellchecked(false);

        if ($this->isSpellcheck() && $this->resultCount > 0) {
            $messages[] = __(
                "No search results for: <b>'%1'</b>. We propose you approaching results.",
                $this->catalogSearchData->getEscapedQueryText()
            );

            $query->setIsSpellchecked(true);
        }

        $this->queryResource->saveSearchResults($query);

        return $messages;
    }

    /**
     * Avoid the search result count to be saved by the original method.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Result   $subject Search result block.
     * @param \Closure $proceed Original method.
     *
     * @return integer
     */
    public function aroundGetResultCount(Result $subject, \Closure $proceed)
    {
        if ($this->resultCount === null) {
            $size = $this->getProductCollection()->getSize();
            $this->resultCount = $size;
        }

        return $this->resultCount;
    }

    /**
     * Return the current layer product collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function getProductCollection()
    {
        return $this->layer->getProductCollection();
    }

    /**
     * Indicates if the current search is spellchecked.
     *
     * @return boolean
     */
    private function isSpellcheck()
    {
        return $this->getProductCollection()->isSpellchecked();
    }
}
