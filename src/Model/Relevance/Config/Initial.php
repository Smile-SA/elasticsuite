<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Model\Relevance\Config;

/**
 * Relevance configuration Initial Reader
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Initial
{
    /**
     * Cache identifier used to store initial config
     */
    const CACHE_ID = 'smile_elasticsuite_initial_config';

    /**
     * Config data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Config metadata
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Class constructor
     *
     * @param \Smile\ElasticSuiteCore\Model\Relevance\Config\Reader\Initial $reader The reader
     * @param \Magento\Framework\App\Cache\Type\Config                      $cache  Cache instance
     */
    public function __construct(
        \Smile\ElasticSuiteCore\Model\Relevance\Config\Reader\Initial $reader,
        \Magento\Framework\App\Cache\Type\Config $cache
    ) {
        $data = $cache->load(self::CACHE_ID);

        if (!$data) {
            $data = $reader->read();
            $cache->save(serialize($data), self::CACHE_ID);
        } else {
            $data = unserialize($data);
        }

        if (isset($data['data'])) {
            $this->data = $data['data'];
        }
        if (isset($data['metadata'])) {
            $this->metadata = $data['metadata'];
        }
    }

    /**
     * Get initial data by given scope
     *
     * @param string $scope Format is scope type and scope code separated by pipe: e.g. "type|code"
     * @return array
     */
    public function getData($scope)
    {
        return isset($this->data[$scope]) ? $this->data[$scope] : [];
    }

    /**
     * Get configuration metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
