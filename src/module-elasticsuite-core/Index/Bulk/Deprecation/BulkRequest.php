<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index\Bulk\Deprecation;

use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Index\Bulk\BulkRequest as BaseBulkRequest;

/**
 * ES bulk (Smile\ElasticsuiteCore\Api\Index\BulkInterface) implementation for ES < 7.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class BulkRequest extends BaseBulkRequest
{
    /**
     * {@inheritdoc}
     */
    public function addDocument(IndexInterface $index, $docId, array $data)
    {
        $this->bulkData[] = ['index' => ['_index' => $index->getName(), '_type' => '_doc', '_id' => $docId]];
        $this->bulkData[] = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDocument(IndexInterface $index, $docId)
    {
        $this->bulkData[] = ['delete' => ['_index' => $index->getName(), '_type' => '_doc', '_id' => $docId]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDocument(IndexInterface $index, $docId, array $data)
    {
        $this->bulkData[] = ['update' => ['_index' => $index->getName(), '_type' => '_doc', '_id' => $docId]];
        $this->bulkData[] = ['doc' => $data];

        return $this;
    }
}
