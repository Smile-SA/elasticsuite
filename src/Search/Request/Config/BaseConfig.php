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

namespace Smile\ElasticSuiteCore\Search\Request\Config;

use Smile\ElasticSuiteCore\Search\Request\Config\BaseConfig\Reader;
use Magento\Framework\Config\CacheInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Bool;

/**
 * ElasticSuite Search requests configuration.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BaseConfig extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for Search Request
     *
     * @var string
     */
    const CACHE_ID = 'elasticsuite_request_declaration';

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * Constructor.
     *
     * @param Reader                 $reader        Config file reader.
     * @param CacheInterface         $cache         Cache interface.
     * @param IndexSettingsInterface $indexSettings Index settings.
     * @param string                 $cacheId       Config cache id.
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        IndexSettingsInterface $indexSettings,
        $cacheId = self::CACHE_ID
    ) {
        parent::__construct($reader, $cache, $cacheId);
        $this->indexSettings = $indexSettings;
        $this->addMappings();
    }

    /**
     * Append the type mapping to search requests configuration.
     *
     * @return Config
     */
    private function addMappings()
    {
        $indicesSettings = $this->indexSettings->getIndicesConfig();

        foreach ($this->_data as $requestName => $requestConfig) {
            $index = $requestConfig['index'];
            $type  = $requestConfig['type'];

            $this->_data[$requestName]['mapping'] = $indicesSettings[$index]['types'][$type]->getMapping();
        }

        return $this;
    }
}
