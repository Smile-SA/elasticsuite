<?php
/**
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\BulkInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Index\TypeInterface;

class Bulk implements BulkInterface
{
    private $bulkData = [];

    public function isEmpty()
    {
        return count($this->bulkData) == 0;
    }

    public function getOperations()
    {
        return $this->bulkData;
    }

    public function addDocument(IndexInterface $index, TypeInterface $type, $docId, $data)
    {
        $this->bulkData[] = ['index' => ['_index' => $index->getName(), '_type' => $type->getName(), '_id' => $docId]];
        $this->bulkData[] = $data;

        return $this;
    }

    public function addDocuments(IndexInterface $index, TypeInterface $type, $data)
    {
        foreach ($data as $docId => $documentData) {
            $this->addDocument($index, $type, $docId, $documentData);
        }

        return $this;
    }

    public function deleteDocument(IndexInterface $index, TypeInterface $type, $docId)
    {
        $this->bulkData[] = ['delete' => ['_index' => $index->getName(), '_type' => $type->getName(), '_id' => $docId]];
        return $this;
    }

    public function deleteDocuments(IndexInterface $index, TypeInterface $type, $docIds)
    {
        foreach ($docIds as $docId) {
            $this->deleteDocument($index, $type, $docId);
        }

        return $this;
    }

    public function updateDocument(IndexInterface $index, TypeInterface $type, $docId, $data)
    {
        return $this;
    }

    public function updateDocuments(IndexInterface $index, TypeInterface $type, $data)
    {
        return $this;
    }
}