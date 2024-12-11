<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Autocomplete;

use Magento\Search\Model\Autocomplete\Item as TermItem;
use Smile\ElasticsuiteCore\Helper\Autocomplete;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Smile\ElasticsuiteCore\Model\Search\QueryStringProviderFactory;

/**
 * Suggested Terms Provider.
 * Based on the Term provider but will manipulate it according to configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SuggestedTermsProvider
{
    /**
     * @var \Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider
     */
    private $termDataProvider;

    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\Autocomplete
     */
    private $helper;

    /**
     * @var \Smile\ElasticsuiteCore\Model\Search\QueryStringProviderFactory
     */
    private $queryStringProviderFactory;

    /**
     * @var null|string
     */
    private $queryString = null;

    /**
     * @var null
     */
    private $terms = null;

    /**
     * @param Autocomplete               $helper                     Autocomplete helper
     * @param TermDataProvider           $termDataProvider           Term data provider
     * @param QueryStringProviderFactory $queryStringProviderFactory Search Query Factory
     */
    public function __construct(
        Autocomplete $helper,
        TermDataProvider $termDataProvider,
        QueryStringProviderFactory $queryStringProviderFactory
    ) {
        $this->helper                     = $helper;
        $this->termDataProvider           = $termDataProvider;
        $this->queryStringProviderFactory = $queryStringProviderFactory;
    }

    /**
     * List of search terms suggested by the search terms data provider, and reworked according to configuration.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array|string[]
     */
    public function getSuggestedTerms()
    {
        if (null === $this->terms) {
            $terms = [];

            if ($this->helper->isExtensionEnabled()) {
                $terms = array_map(
                    function (TermItem $termItem) {
                        return trim($termItem->getTitle());
                    },
                    $this->termDataProvider->getItems()
                );

                $hasAlreadyStoppedExtending = false;
                if ($this->helper->isExtensionStoppedOnMatch()) {
                    if (array_search(trim($this->getQueryString()), $terms) !== false) {
                        $terms                      = [$this->getQueryString()];
                        $hasAlreadyStoppedExtending = true;
                    }
                }

                if ($this->helper->isExtensionLimited() && !$hasAlreadyStoppedExtending) {
                    $terms = array_slice($terms, 0, (int) $this->helper->getExtensionSize());
                }

                if ($this->helper->isPreservingBaseQuery() && !$hasAlreadyStoppedExtending) {
                    array_unshift($terms, $this->getQueryString());
                }
            }

            if (empty($terms)) {
                $terms = [$this->getQueryString()];
            }

            $this->terms = array_values(array_unique($terms));
        }

        return $this->terms;
    }

    /**
     * Retrieve current query string
     *
     * @return string
     */
    private function getQueryString()
    {
        if ($this->queryString === null) {
            $this->queryString = $this->queryStringProviderFactory->create()->get();
        }

        return $this->queryString;
    }
}
