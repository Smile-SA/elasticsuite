<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index\Bulk;

use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Bulk request representation interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BulkRequestInterface
{
    /** @var string */
    const ACTION_CREATION = 'create';

    /** @var string */
    const ACTION_UPDATE = 'update';

    /** @var string */
    const ACTION_DELETE = 'delete';

    /** @var string */
    const ACTION_INDEX = 'index';

    /**
     * Indicates if the current bulk contains operation.
     *
     * @return boolean
     */
    public function isEmpty();

    /**
     * Return list of operations to be executed as an array.
     *
     * @return array
     */
    public function getOperations();

    /**
     * Add a single document to the index.
     *
     * @param IndexInterface $index Index the document has to be added to.
     * @param string|integer $docId Document id.
     * @param array          $data  Document data.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface
     */
    public function addDocument(IndexInterface $index, $docId, array $data);

    /**
     * Add a several documents to the index.
     *
     * $data format have to be an array of all documents with document id as key.
     *
     * @param IndexInterface $index Index the documents have to be added to.
     * @param array          $data  Document data.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface
     */
    public function addDocuments(IndexInterface $index, array $data);

    /**
     * Delete a document from the index.
     *
     * @param IndexInterface $index Index the document has to be delete from.
     * @param string|integer $docId Document id.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface
     */
    public function deleteDocument(IndexInterface $index, $docId);

    /**
     * Delete multiple documents from the index.
     *
     * @param IndexInterface $index  Index the document has to be delete from.
     * @param array          $docIds Ids of the deleted documents.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface
     */
    public function deleteDocuments(IndexInterface $index, array $docIds);

    /**
     * Update a single document to the index.
     *
     * Note : only updated fields are needed into data.
     *        Others fields are kept untouched by the update operation.
     *
     * @param IndexInterface $index Index the document has to be added to.
     * @param string|integer $docId Document id.
     * @param array          $data  Updated data.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface Self reference.
     */
    public function updateDocument(IndexInterface $index, $docId, array $data);

    /**
     * Update a several documents to the index.
     *
     * $data format have to be an array of all documents update data with document id as key.
     *
     * @param IndexInterface $index Index the documents have to be added to.
     * @param array          $data  Document data.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface Self reference.
     */
    public function updateDocuments(IndexInterface $index, array $data);
}
