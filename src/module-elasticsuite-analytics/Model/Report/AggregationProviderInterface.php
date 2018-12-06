<?php

namespace Smile\ElasticsuiteAnalytics\Model\Report;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

interface AggregationProviderInterface
{
    /**
     * @return BucketInterface
     */
    public function getAggregation();
}