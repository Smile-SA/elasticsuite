<?php

namespace Smile\ElasticSuiteCore\Api\Index;

interface BulkInterface
{
    public function isEmpty();

    public function getOperations();

    public function addDocument(IndexInterface $index, TypeInterface $type, $docId, $data);

    public function addDocuments(IndexInterface $index, TypeInterface $type, $data);

    public function deleteDocument(IndexInterface $index, TypeInterface $type, $docId);

    public function deleteDocuments(IndexInterface $index, TypeInterface $type, $docIds);

    public function updateDocument(IndexInterface $index, TypeInterface $type, $docId, $data);

    public function updateDocuments(IndexInterface $index, TypeInterface $type, $data);
}