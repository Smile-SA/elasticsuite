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

use Magento\Framework\Search\EntityMetadata;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\ObjectManagerInterface;

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
     * @var EntityMetadata
     */
    private $entityMetadata;

    /**
     * @var string
     */
    private $instanceName;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager  Object manager.
     * @param EntityMetadata         $entityMetadata Entity metadata configurartion.
     * @param string                 $instanceName   Object instantiated type.
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        EntityMetadata $entityMetadata,
        $instanceName = 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response\Document'
    ) {
        $this->entityMetadata  = $entityMetadata;
        $this->objectManager   = $objectManager;
        $this->instanceName    = $instanceName;
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

        $entityIdFieldName = $this->entityMetadata->getEntityId();

        $rawDocument[Document::ID] = $rawDocument[$entityIdFieldName];

        return $this->objectManager->create($this->instanceName, ['data' => $rawDocument]);
    }
}
