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
namespace Smile\ElasticsuiteCore\Plugin\Setup;

/**
 * Search Config options plugin. Used to add Elasticsuite as authorized engine.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchConfigOptionsListPlugin
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Setup\Model\SearchConfigOptionsList $subject Search Config Options List
     * @param array                                        $result  Search Result Configuration
     *
     * @return array
     */
    public function afterGetAvailableSearchEngineList(\Magento\Setup\Model\SearchConfigOptionsList $subject, $result)
    {
        if (!is_array($result)) {
            $result = [];
        }

        return array_merge($result, ['elasticsuite' => 'Smile Elastic Suite']);
    }
}
