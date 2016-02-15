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
