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

use Magento\Framework\Module\Dir;

/**
 * Schema Locator for initial configuration files
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SchemaLocator extends \Magento\Framework\App\Config\Initial\SchemaLocator
{
    /**
     * Schema file for elasticsuite initial configuration validation
     */
    const SCHEMA_FILE = "elasticsuite_relevance_initial_config.xsd";

    /**
     * Reader constructor
     *
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader The module reader
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $moduleDir     = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Smile_ElasticsuiteCore');
        $this->_schema = $moduleDir . '/' . self::SCHEMA_FILE;
    }
}
