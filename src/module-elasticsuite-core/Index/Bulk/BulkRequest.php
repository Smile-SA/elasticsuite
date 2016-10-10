<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index\Bulk;

use Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Default implementation for ES bulk (Smile\ElasticsuiteCore\Api\Index\BulkInterface).
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BulkRequest implements BulkRequestInterface
{
    /**
     * Bulk operation stack.
     *
     * @var array
     */
    private $bulkData = [];

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return count($this->bulkData) == 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperations()
    {
        return $this->bulkData;
    }

    /**
     * {@inheritdoc}
     */
    public function addDocument(IndexInterface $index, TypeInterface $type, $docId, array $data)
    {
        $this->bulkData[] = ['index' => ['_index' => $index->getName(), '_type' => $type->getName(), '_id' => $docId]];
        $this->bulkData[] = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addDocuments(IndexInterface $index, TypeInterface $type, array $data)
    {
        foreach ($data as $docId => $documentData) {
            $this->addDocument($index, $type, $docId, $documentData);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDocument(IndexInterface $index, TypeInterface $type, $docId)
    {
        $this->bulkData[] = ['delete' => ['_index' => $index->getName(), '_type' => $type->getName(), '_id' => $docId]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDocuments(IndexInterface $index, TypeInterface $type, array $docIds)
    {
        foreach ($docIds as $docId) {
            $this->deleteDocument($index, $type, $docId);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDocument(IndexInterface $index, TypeInterface $type, $docId, array $data)
    {
        $this->bulkData[] = ['update' => ['_index' => $index->getName(), '_type' => $type->getName(), '_id' => $docId]];
        $this->bulkData[] = ['doc' => $data];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDocuments(IndexInterface $index, TypeInterface $type, array $data)
    {
        foreach ($data as $docId => $documentData) {
            $this->updateDocument($index, $type, $docId, $documentData);
        }

        return $this;
    }
}
