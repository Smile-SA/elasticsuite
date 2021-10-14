<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

/**
 * Elasticsuite search config options list
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchConfigOptionsList extends \Magento\Setup\Model\SearchConfigOptionsList
{
    /**
     * {@inheritDoc}
     */
    public function getAvailableSearchEngineList(): array
    {
        $list = parent::getAvailableSearchEngineList();

        return array_merge($list, ['elasticsuite' => 'Smile Elastic Suite']);
    }
}
