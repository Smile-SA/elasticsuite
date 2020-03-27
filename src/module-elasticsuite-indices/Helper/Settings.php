<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Settings helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Settings extends AbstractHelper
{
    /**
     * @var string
     */
    const INDICES_SETTINGS_CONFIG_XML_PREFIX = 'smile_elasticsuite_indices';

    /**
     * Returns mapping for indices
     **
     * @return array
     */
    public function getMapping(): array
    {
        return $this->normalize($this->getSerializedConfigValue('indices_mapping/mapping'));
    }

    /**
     * Get serialized config value
     *
     * @param string $key Key.
     * @return mixed
     */
    public function getSerializedConfigValue($key)
    {
        $json = ObjectManager::getInstance()->get(Json::class);

        return $json->unserialize($this->getConfigValue($key));
    }

    /**
     * Retrieve a configuration value by its key
     *
     * @param string $key The configuration key
     *
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(self::INDICES_SETTINGS_CONFIG_XML_PREFIX . "/" . $key);
    }

    /**
     * Normalize config data.
     *
     * @param array $data Data.
     * @return array
     */
    private function normalize($data)
    {
        $result = [];
        foreach ($data as $item) {
            $result[$item['key']][] = $item['value'];
        }

        return $result;
    }
}
