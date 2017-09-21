<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config;

use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Elasticsuite Relevance Configuration Scope Pool.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ScopePool
{
    const CACHE_TAG = 'config_scopes';

    /**
     * @var \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\ReaderPool
     */
    private $readerPool;

    /**
     * @var \Magento\Framework\App\Config\DataFactory
     */
    private $dataFactory;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheId;

    /**
     * @var \Magento\Framework\App\Config\DataInterface[]
     */
    private $scopes = [];

    /**
     * @var ScopeCodeResolver
     */
    private $scopeCodeResolver;

    /**
     * @param \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\ReaderPool $readerPool        Reader Pool
     * @param \Magento\Framework\App\Config\DataFactory                               $dataFactory       Config Data Factory
     * @param \Magento\Framework\App\CacheInterface                                   $cache             Cache Interface
     * @param \Magento\Framework\App\Config\ScopeCodeResolver                         $scopeCodeResolver Scope Code Resolver
     * @param string                                                                  $cacheId           Cache Id
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\ReaderPool $readerPool,
        \Magento\Framework\App\Config\DataFactory $dataFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Config\ScopeCodeResolver $scopeCodeResolver,
        $cacheId = 'default_config_cache'
    ) {
        $this->readerPool        = $readerPool;
        $this->dataFactory       = $dataFactory;
        $this->cache             = $cache;
        $this->cacheId           = $cacheId;
        $this->scopeCodeResolver = $scopeCodeResolver;
    }

    /**
     * Retrieve config section
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param string                                    $scopeType Scope Type
     * @param string|\Magento\Framework\DataObject|null $scopeCode Scope Code
     *
     * @return \Magento\Framework\App\Config\DataInterface
     */
    public function getScope($scopeType, $scopeCode = null)
    {
        $scopeCode = $this->scopeCodeResolver->resolve($scopeType, $scopeCode);

        $code = $scopeType . '|' . $scopeCode;

        if (!isset($this->scopes[$code])) {
            $cacheKey = $this->cacheId . '|' . $code;
            $data     = $this->cache->load($cacheKey);

            if ($data) {
                $data = json_decode($data, true); // Enforce decoding JSON as associative array.
            } else {
                $reader = $this->readerPool->getReader($scopeType);
                $data   = ($scopeType === ScopeConfigInterface::SCOPE_TYPE_DEFAULT) ? $data = $reader->read() : $reader->read($scopeCode);

                $this->cache->save(json_encode($data), $cacheKey, [self::CACHE_TAG]);
            }

            $this->scopes[$code] = $this->dataFactory->create(['data' => (array) $data]);
        }

        return $this->scopes[$code];
    }

    /**
     * Clear cache of all scopes
     *
     * @return void
     */
    public function clean()
    {
        $this->scopes = [];
        $this->cache->clean([self::CACHE_TAG]);
    }
}
