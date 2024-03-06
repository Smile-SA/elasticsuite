<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig;

use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial\Reader;

/**
 * Relevance configuration Initial Reader
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @param \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial\Reader $reader The reader
     * @param \Magento\Framework\App\Cache\Type\Config                                    $cache  Cache instance
     */
    public function __construct(
        Reader $reader,
        \Magento\Framework\App\Cache\Type\Config $cache
    ) {
        $data = $cache->load(self::CACHE_ID);

        if (!$data) {
            $data = serialize($reader->read());
            $cache->save($data, self::CACHE_ID, [\Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::CACHE_TAG]);
        }

        $data = unserialize($data);

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
