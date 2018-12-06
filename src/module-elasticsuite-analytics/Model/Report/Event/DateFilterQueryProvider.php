<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model\Report\Event;


use Smile\ElasticsuiteAnalytics\Model\Report\QueryProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteAnalytics\Model\Report\Context;

class DateFilterQueryProvider implements QueryProviderInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var Context
     */
    private $context;

    public function __construct(QueryFactory $queryFactory, Context $context)
    {
        $this->queryFactory = $queryFactory;
        $this->context      = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery()
    {
        $queryParams = ['field' => 'date', 'bounds' => $this->getDateRange()];

        return $this->queryFactory->create(QueryInterface::TYPE_RANGE, $queryParams);
    }

    private function getDateRange()
    {
        $range = $this->context->getDateRange();

        return ['gte' => $range['from'], 'lte' => $range['to']];
    }
}