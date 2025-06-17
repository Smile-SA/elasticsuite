<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Model\Data\Checker\Session;

use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker\AbstractDataChecker;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckerInterface;

/**
 * Behavioral data checker for undefined session ids in sessions.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class UndefinedSessionId extends AbstractDataChecker implements DataCheckerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function getInvalidDocsDescription(int $docCount): string
    {
        return sprintf("%d sessions with an undefined id.", $docCount);
    }

    /**
     * Build search request trying to find session docs with a session_id valued to null.
     *
     * @param int $storeId Store id.
     *
     * @return RequestInterface
     */
    protected function getSearchRequest($storeId): RequestInterface
    {
        $queryFilters = ['session_id' => 'null'];

        return $this->searchRequestBuilder->create($storeId, SessionIndexInterface::INDEX_IDENTIFIER, 0, 0, null, [], [], $queryFilters);
    }
}
