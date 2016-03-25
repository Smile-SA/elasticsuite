<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\BaseConfig;

use Magento\Framework\Module\Dir;
use Magento\Framework\Config\SchemaLocatorInterface;

/**
 * Locate schema validation for ElasticSuite search requests configuration files.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SchemaLocator implements SchemaLocatorInterface
{
    const SCHEMA_FILE = 'elasticsuite_search_request.xsd';

    /**
     * Path to corresponding XSD file with validation rules for both individual and merged configs.
     *
     * @var string
     */
    private $schema;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader Module directory reader.
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $moduleDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Smile_ElasticSuiteCore');
        $this->schema = $moduleDir . '/' . self::SCHEMA_FILE;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerFileSchema()
    {
        return $this->schema;
    }
}
