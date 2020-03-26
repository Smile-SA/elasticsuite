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

namespace Smile\ElasticsuiteCore\Index\Indices\Config;

use Magento\Framework\Module\Dir;
use Magento\Framework\Config\SchemaLocatorInterface;

/**
 * Locate schema validation for elasticsearch indices configuration files.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SchemaLocator implements SchemaLocatorInterface
{
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
        $moduleDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Smile_ElasticsuiteCore');
        $this->schema = $moduleDir . '/elasticsuite_indices.xsd';
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
