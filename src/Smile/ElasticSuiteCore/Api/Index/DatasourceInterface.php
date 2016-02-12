<?php

namespace Smile\ElasticSuiteCore\Api\Index;

interface DatasourceInterface
{
    /**
     *
     * @param int   $storeId
     * @param array $entityIds
     *
     * @return array
     */
    public function addData($storeId, array $indexData);
}