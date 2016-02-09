<?php

namespace Smile\ElasticSuiteCore\Index\Indices;

use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\CacheInterface;

class Config extends \Magento\Framework\Config\Data
{
    /** Cache ID for Search Request*/
    const CACHE_ID = 'indices_config';

    /**
     * @param \Magento\Framework\Search\Request\Config\FilesystemReader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(Filesystem $reader, CacheInterface $cache, $cacheId = self::CACHE_ID) {
        parent::__construct($reader, $cache, $cacheId);
    }
}
