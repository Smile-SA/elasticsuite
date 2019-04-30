<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms\SpellcheckedTerms;

use Smile\ElasticsuiteAnalytics\Model\Report\QueryProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

/**
 * Spellchecked terms QueryProvider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class QueryProvider implements QueryProviderInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * QueryProvider constructor.
     *
     * @param QueryFactory $queryFactory Query factory.
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery()
    {
        return $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'page.search.is_spellchecked', 'value' => true]);
    }
}
