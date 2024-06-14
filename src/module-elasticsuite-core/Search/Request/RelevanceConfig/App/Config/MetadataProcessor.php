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
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config;

/**
 * Elasticsuite Relevance Configuration Scope Code Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MetadataProcessor extends \Magento\Framework\App\Config\MetadataProcessor
{
    /**
     * Dummy method, we do not need advanced processing for search relevance configuration.
     *
     * @param array $data Configuration data
     *
     * @return array
     */
    public function process(array $data)
    {
        return $data;
    }
}
