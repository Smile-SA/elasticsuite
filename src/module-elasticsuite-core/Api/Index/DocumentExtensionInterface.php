<?php
/**
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vladimir Bratukhin <insyon@gmail.com>
 * @copyright 2017 Smile
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * ES Document Extension service contract interface
 *
 * Interface DocumentExtensionInterface
 * @package Smile\ElasticsuiteCore\Api\Index
 *
 * @author Vladimir Bratukhin <insyon@gmail.com>
 */
interface DocumentExtensionInterface
{
    const KEY = 'extension_data';

    /**
     * Return array ready for ES document
     *
     * @return array
     */
    public function toArray();
}
