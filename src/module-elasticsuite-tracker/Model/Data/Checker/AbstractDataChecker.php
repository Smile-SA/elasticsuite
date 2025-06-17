<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Data\Checker;

use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Abstract data checker.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
abstract class AbstractDataChecker
{
    /**
     * @var DataCheckResultFactory
     */
    protected $checkResultFactory;

    /**
     * @var Builder
     */
    protected $searchRequestBuilder;

    /**
     * @var SearchEngineInterface
     */
    protected $searchEngine;

    /**
     * @var ?DataFixerInterface
     */
    protected $dataFixer;

    /**
     * Constructor.
     *
     * @param DataCheckResultFactory  $checkResultFactory   Data check result factory.
     * @param Builder                 $searchRequestBuilder Search request builder.
     * @param SearchEngineInterface   $searchEngine         Search engine.
     * @param DataFixerInterface|null $dataFixer            Invalid data fixer.
     */
    public function __construct(
        DataCheckResultFactory $checkResultFactory,
        Builder $searchRequestBuilder,
        SearchEngineInterface $searchEngine,
        ?DataFixerInterface $dataFixer = null
    ) {
        $this->checkResultFactory   = $checkResultFactory;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->searchEngine         = $searchEngine;
        $this->dataFixer            = $dataFixer;
    }

    /**
     * Perform a check based on search request implemented in child classes and return a check result object.
     *
     * @param int $storeId Store id.
     *
     * @return DataCheckResult
     */
    public function check($storeId): DataCheckResult
    {
        /** @var DataCheckResult $checkResult */
        $checkResult = $this->checkResultFactory->create([]);

        try {
            $request = $this->getSearchRequest($storeId);
            $response = $this->searchEngine->search($request);
            if ($response->count() > 0) {
                $checkResult->setInvalidData(true);
                $checkResult->setDescription($this->getInvalidDocsDescription((int) $response->count()));
            }
        } catch (\LogicException $e) {
            ;
        }

        return $checkResult;
    }

    /**
     * {@inheritDoc}
     */
    public function hasDataFixer(): bool
    {
        return $this->dataFixer instanceof DataFixerInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function getDataFixer(): ?DataFixerInterface
    {
        return $this->dataFixer;
    }

    /**
     * Returns the search request to perform the check for the provided store id.
     *
     * @param mixed $storeId Store ID.
     *
     * @return RequestInterface
     */
    abstract protected function getSearchRequest($storeId): RequestInterface;

    /**
     * Get the invalid documents description.
     *
     * @param int $docCount Number of invalid documents.
     *
     * @return string
     */
    abstract protected function getInvalidDocsDescription(int $docCount): string;
}
