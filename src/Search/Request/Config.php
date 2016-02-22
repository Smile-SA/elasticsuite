<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request;

use Smile\ElasticSuiteCore\Search\Request\Config\Reader;
use Magento\Framework\Config\CacheInterface;

class Config extends \Magento\Framework\Config\Data
{
    /** Cache ID for Search Request*/
    const CACHE_ID = 'request_declaration';

    /**
     * @param \Magento\Framework\Search\Request\Config\FilesystemReader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(Reader $reader, CacheInterface $cache, $cacheId = self::CACHE_ID) {
        parent::__construct($reader, $cache, $cacheId);
    }
}