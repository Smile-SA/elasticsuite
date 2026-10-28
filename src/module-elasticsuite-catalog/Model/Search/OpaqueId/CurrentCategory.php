<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Search\OpaqueId;

use Smile\ElasticsuiteCore\Api\Client\OpaqueIdPartProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Adds the current category of the search context (category being viewed, or category filter applied in search)
 * to the X-Opaque-Id, e.g. "cat=4".
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CurrentCategory implements OpaqueIdPartProviderInterface
{
    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * Constructor.
     *
     * @param ContextInterface $searchContext Search context.
     */
    public function __construct(ContextInterface $searchContext)
    {
        $this->searchContext = $searchContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getParts(): array
    {
        // The search context has no current category until one is set.
        $category = $this->searchContext->getCurrentCategory();

        return ['cat' => $category ? $category->getId() : null];
    }
}
