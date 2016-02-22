<?php

namespace Smile\ElasticSuiteCore\Search\Request\Config;

use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\ValidationStateInterface;

/**
 * ElasticSuite search requests configuration reader.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Reader extends Filesystem
{
    /**
     * Reader supported filename.
     *
     * @var string
     */
    const FILENAME = 'elasticsuite_search_request.xml';

    // @codingStandardsIgnoreStart
    /**
     * List of attributes by XPath used as ids during the file merge process.
     *
     * @var array
     */
    protected $_idAttributes = [

    ];
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = self::FILENAME,
        $idAttributes = [],
        $domDocumentClass = 'Magento\Framework\Config\Dom',
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
