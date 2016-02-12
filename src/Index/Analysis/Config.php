<?php
/**
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Index\Analysis;

use Smile\ElasticSuiteCore\Index\Analysis\Config\Reader;
use Magento\Framework\Config\CacheInterface;

class Config extends \Magento\Framework\Config\Data
{
    /** Cache ID for Search Request*/
    const CACHE_ID = 'analysis_config';

    /**
     * @param \Magento\Framework\Search\Request\Config\FilesystemReader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(Reader $reader, CacheInterface $cache, $cacheId = self::CACHE_ID) {
        parent::__construct($reader, $cache, $cacheId);
    }

    public function get($language = null, $default = null)
    {
        if ($language == null) {
            $language = 'default';
        }

        $data = parent::get($language, null);

        if ($data == null) {
            $data = $this->get('default', $default);
        }

        return $data;
    }
}
