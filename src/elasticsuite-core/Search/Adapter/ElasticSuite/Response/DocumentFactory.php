<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\EntityMetadata;
use Magento\Framework\Search\DocumentFieldFactory;
use Magento\Framework\Api\Search\DocumentFactory as GenericDocumentFactory;
use Magento\Framework\Api\Search\Document;

/**
 * Generate document from ES hit response.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DocumentFactory
{
    /**
     * @var string
     */
    const SCORE_DOC_FIELD_NAME = "score";

    /**
     * @var GenericDocumentFactory
     */
    private $documentFactory;

    /**
     * @var EntityMetadata
     */
    private $entityMetadata;

    /**
     * Constructor.
     *
     * @param GenericDocumentFactory $documentFactory Generic document factory used to build documents.
     * @param EntityMetadata         $entityMetadata  Entity metadata configurartion.
     */
    public function __construct(GenericDocumentFactory $documentFactory, EntityMetadata $entityMetadata)
    {
        $this->documentFactory = $documentFactory;
        $this->entityMetadata  = $entityMetadata;
    }

    /**
     * Create search dcument instance from a ES hit.
     *
     * @param array $rawDocument ES raw hit.
     *
     * @return Document
     */
    public function create($rawDocument)
    {
        /** @var \Magento\Framework\Search\DocumentField[] $fields */
        $fields            = [];
        $documentId        = null;
        $entityIdFieldName = $this->entityMetadata->getEntityId();

        $fields[Document::ID] = $rawDocument[$entityIdFieldName];
        $fields[self::SCORE_DOC_FIELD_NAME] = $rawDocument['_score'];

        $documentParams = ['data' => $fields, 'documentId' => $documentId];

        return $this->documentFactory->create($documentParams);
    }
}
