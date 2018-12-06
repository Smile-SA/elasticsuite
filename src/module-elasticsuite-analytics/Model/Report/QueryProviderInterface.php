<?php

namespace Smile\ElasticsuiteAnalytics\Model\Report;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

interface QueryProviderInterface
{
    /**
     * @return QueryInterface
     */
    public function getQuery();
}