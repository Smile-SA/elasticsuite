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

namespace Smile\ElasticSuiteCore\Search\Request;

use Smile\ElasticSuiteCore\Search\Request\Config\Reader;
use Magento\Framework\Config\CacheInterface;

/**
 * ElasticSuite Search requests configuration.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for Search Request
     *
     * @var string
     */
    const CACHE_ID = 'elasticsuite_request_declaration';

    /**
     * Constructor.
     *
     * @param Reader         $reader  Config file reader.
     * @param CacheInterface $cache   Cache interface.
     * @param string         $cacheId Config cache id.
     */
    public function __construct(Reader $reader, CacheInterface $cache, $cacheId = self::CACHE_ID)
    {
        parent::__construct($reader, $cache, $cacheId);
    }
}
