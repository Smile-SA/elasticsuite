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

namespace Smile\ElasticSuiteCore\Search\Request;

use Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\BaseConfig;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Search request container configuration implementation.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ContainerConfiguration implements ContainerConfigurationInterface
{
    /**
     * @var string
     */
    private $containerName;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var BaseConfig
     */
    private $baseConfig;

    /**
     * @var IndexOperationInterface
     */
    private $indexManager;

    /**
     * Constructor.
     *
     * @param string                  $containerName Search request container name.
     * @param integer                 $storeId       Store id.
     * @param BaseConfig              $baseConfig    XML file configuration.
     * @param IndexOperationInterface $indexManager  Index manager (used to load mappings).
     */
    public function __construct($containerName, $storeId, BaseConfig $baseConfig, IndexOperationInterface $indexManager)
    {
        $this->containerName = $containerName;
        $this->storeId       = $storeId;
        $this->baseConfig    = $baseConfig;
        $this->indexManager  = $indexManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->containerName;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexName()
    {
        return $this->readBaseConfigParam('index');
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeName()
    {
        return $this->readBaseConfigParam('type');
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->readBaseConfigParam('label');
    }

    /**
     * {@inheritDoc}
     */
    public function getMapping()
    {
        $indexName = $this->getIndexName();
        $typeName  = $this->getTypeName();
        $index     = $this->indexManager->getIndexByName($indexName, $this->storeId);
        $type      = $index->getType($typeName);

        return $type->getMapping();
    }

    /**
     * Read configuration param from base config.
     *
     * @param string $param Param name.
     *
     * @return mixed
     */
    private function readBaseConfigParam($param)
    {
        return $this->baseConfig->get($this->containerName . '/' . $param);
    }
}
