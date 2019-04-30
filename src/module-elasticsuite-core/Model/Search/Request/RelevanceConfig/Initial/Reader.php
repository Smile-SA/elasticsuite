<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial;

use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\DomFactory;
use Magento\Framework\Config\ConverterInterface;

/**
 * Relevance configuration initial reader
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Reader extends \Magento\Framework\App\Config\Initial\Reader
{
    /**
     * The relevance configuration default filename
     */
    const FILE_NAME = 'elasticsuite_relevance.xml';

    /**
     * @param FileResolverInterface $fileResolver  The file resolver
     * @param ConverterInterface    $converter     Converter
     * @param SchemaLocator         $schemaLocator The schema locator
     * @param DomFactory            $domFactory    The DOM factory
     * @param string                $fileName      The file name
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        ConverterInterface $converter,
        SchemaLocator $schemaLocator,
        DomFactory $domFactory,
        $fileName = self::FILE_NAME
    ) {
        parent::__construct($fileResolver, $converter, $schemaLocator, $domFactory, $fileName);
    }
}
