<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
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

/**
 * ElasticSuite analysis configuration.
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for analysis configuration.
     *
     * @var string
     */
    const CACHE_ID = 'analysis_config';

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Search\Request\Config\FilesystemReader $reader  Config file reader.
     * @param \Magento\Framework\Config\CacheInterface                  $cache   Cache instance.
     * @param string                                                    $cacheId Default config cache id.
     */
    public function __construct(Reader $reader, CacheInterface $cache, $cacheId = self::CACHE_ID)
    {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Return analysis config by language.
     *
     * @param string $language A language code (eg: en, fr, ...).
     * @param mixed  $default  Default value if no config is found.
     *
     * @return mixed
     */
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
