<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Reader;

/**
 * Relevance configuration initial reader
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Initial
{
    /**
     * File locator
     *
     * @var \Magento\Framework\Config\FileResolverInterface
     */
    protected $fileResolver;

    /**
     * Config converter
     *
     * @var  \Magento\Framework\App\Config\Initial\Converter
     */
    protected $converter;

    /**
     * Config file name
     *
     * @var string
     */
    protected $fileName;

    /**
     * Class of dom configuration document used for merge
     *
     * @var string
     */
    protected $domDocumentClass;

    /**
     * Scope priority loading scheme
     *
     * @var array
     */
    protected $scopePriorityScheme = ['global'];

    /**
     * Path to corresponding XSD file with validation rules for config
     *
     * @var string
     */
    protected $schemaFile;

    /**
     * @param \Magento\Framework\Config\FileResolverInterface                      $fileResolver  The file resolver
     * @param \Magento\Framework\Config\ConverterInterface                         $converter     Converter
     * @param \Smile\ElasticSuiteCore\Model\Relevance\Config\Initial\SchemaLocator $schemaLocator The schema locator
     * @param \Magento\Framework\Config\DomFactory                                 $domFactory    The DOM factory
     * @param string                                                               $fileName      The file name
     */
    public function __construct(
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        \Magento\Framework\Config\ConverterInterface $converter,
        \Smile\ElasticSuiteCore\Model\Relevance\Config\Initial\SchemaLocator $schemaLocator,
        \Magento\Framework\Config\DomFactory $domFactory,
        $fileName = 'relevance.xml'
    ) {
        $this->schemaFile = $schemaLocator->getSchema();
        $this->fileResolver = $fileResolver;
        $this->converter = $converter;
        $this->domFactory = $domFactory;
        $this->fileName = $fileName;
    }

    /**
     * Read configuration scope
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function read()
    {
        $fileList = [];
        foreach ($this->scopePriorityScheme as $scope) {
            $directories = $this->fileResolver->get($this->fileName, $scope);
            foreach ($directories as $key => $directory) {
                $fileList[$key] = $directory;
            }
        }

        if (!count($fileList)) {
            return [];
        }

        /** @var \Magento\Framework\Config\Dom $domDocument */
        $domDocument = null;
        foreach ($fileList as $file) {
            try {
                if (!$domDocument) {
                    $domDocument = $this->domFactory->createDom(['xml' => $file, 'schemaFile' => $this->schemaFile]);
                } else {
                    $domDocument->merge($file);
                }
            } catch (\Magento\Framework\Config\Dom\ValidationException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase("Invalid XML in file %1:\n%2", [$file, $e->getMessage()])
                );
            }
        }

        $output = [];
        if ($domDocument) {
            $output = $this->converter->convert($domDocument->getDom());
        }

        return $output;
    }
}
